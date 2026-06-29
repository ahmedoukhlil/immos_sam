<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Nom de la table
     */
    protected $table = 'users';

    /**
     * Clé primaire personnalisée
     */
    protected $primaryKey = 'idUser';

    /**
     * Désactiver les timestamps
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'users',  // Nom d'utilisateur (selon immos.md)
        'mdp',    // Mot de passe (selon immos.md)
        'role',
    ];

    /**
     * Nom de la colonne pour l'authentification (utilisé par Laravel)
     * Note: getAuthIdentifierName() doit retourner le nom de la colonne utilisée pour identifier l'utilisateur
     * mais Auth::id() retourne toujours la valeur de la clé primaire
     */
    public function getAuthIdentifierName()
    {
        return $this->primaryKey; // Retourner 'idUser' pour que Auth::id() fonctionne correctement
    }

    /**
     * Récupérer l'identifiant pour l'authentification (retourne la clé primaire)
     */
    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->primaryKey); // Retourner idUser
    }

    /**
     * Récupérer le mot de passe pour l'authentification
     */
    public function getAuthPassword()
    {
        return $this->getAttribute('mdp');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'mdp',  // Mot de passe (selon immos.md)
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Pas de cast 'hashed' car les mots de passe peuvent être en clair dans la base existante
        ];
    }

    /**
     * RELATIONS
     */

    /**
     * Relation avec les immobilisations (Gesimmo) créées par l'utilisateur
     * Note: Cette relation peut nécessiter une colonne user_id dans la table gesimmo
     */
    public function immobilisations(): HasMany
    {
        return $this->hasMany(Gesimmo::class, 'user_id', 'idUser');
    }

    /**
     * Relation avec les inventaires créés par l'utilisateur
     */
    public function inventairesCreated(): HasMany
    {
        return $this->hasMany(Inventaire::class, 'created_by');
    }

    /**
     * Relation avec les inventaires clôturés par l'utilisateur
     */
    public function inventairesClosed(): HasMany
    {
        return $this->hasMany(Inventaire::class, 'closed_by');
    }

    /**
     * Relation avec les scans d'inventaire effectués par l'utilisateur
     */
    public function inventaireScans(): HasMany
    {
        return $this->hasMany(InventaireScan::class, 'user_id');
    }

    /**
     * Relation avec les inventaire_localisations assignées à l'utilisateur
     */
    public function inventaireLocalisations(): HasMany
    {
        return $this->hasMany(InventaireLocalisation::class, 'user_id');
    }

    /**
     * Relation avec les entrées de stock créées par l'utilisateur
     */
    public function stockEntrees(): HasMany
    {
        return $this->hasMany(\App\Models\StockEntree::class, 'created_by', 'idUser');
    }

    /**
     * Relation avec les sorties de stock créées par l'utilisateur
     */
    public function stockSorties(): HasMany
    {
        return $this->hasMany(\App\Models\StockSortie::class, 'created_by', 'idUser');
    }

    /**
     * SCOPES
     */

    /**
     * Scope pour filtrer les administrateurs
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope pour filtrer les administrateurs stock
     */
    public function scopeAdminStocks(Builder $query): Builder
    {
        return $query->where('role', 'admin_stock');
    }

    /**
     * Scope pour filtrer les agents
     */
    public function scopeAgents(Builder $query): Builder
    {
        return $query->where('role', 'agent');
    }

    /**
     * Scope pour filtrer par rôle
     */
    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * ACCESSORS
     */

    /**
     * Retourne le nom du rôle en français
     */
    public function getRoleNameAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrateur',
            'admin_stock' => 'Admin Stock',
            'agent' => 'Agent',
            default => 'Non défini',
        };
    }

    /**
     * METHODS
     */

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur est administrateur stock
     */
    public function isAdminStock(): bool
    {
        return $this->role === 'admin_stock';
    }

    /**
     * Vérifie si l'utilisateur est agent
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Vérifie si l'utilisateur peut gérer les inventaires
     * Admin et Agent peuvent gérer les inventaires
     */
    public function canManageInventaire(): bool
    {
        return in_array($this->role, ['admin', 'agent']);
    }

    /**
     * Trouve un utilisateur par son nom d'utilisateur
     * Gère automatiquement les différences de structure entre environnements
     * 
     * @param string $username
     * @return User|null
     */
    public static function findByUsername(string $username): ?self
    {
        // Essayer avec la colonne 'users' (structure attendue)
        try {
            return static::where('users', $username)->first();
        } catch (\Exception $e) {
            // Si ça échoue, essayer avec DB::table directement
            try {
                $userData = \DB::table('users')
                    ->where('users', $username)
                    ->first();
                
                if ($userData) {
                    $userId = $userData->idUser ?? $userData->id ?? null;
                    return $userId ? static::find($userId) : null;
                }
            } catch (\Exception $e2) {
                // Dernière tentative avec SQL brut
                try {
                    $userData = \DB::selectOne(
                        'SELECT * FROM users WHERE users = ? LIMIT 1',
                        [$username]
                    );
                    
                    if ($userData) {
                        $userId = $userData->idUser ?? $userData->id ?? null;
                        return $userId ? static::find($userId) : null;
                    }
                } catch (\Exception $e3) {
                    \Log::error('Erreur lors de la recherche d\'utilisateur', [
                        'username' => $username,
                        'errors' => [
                            'eloquent' => $e->getMessage(),
                            'query_builder' => $e2->getMessage(),
                            'raw_sql' => $e3->getMessage()
                        ]
                    ]);
                }
            }
        }
        
        return null;
    }
}
