<?php
namespace CookieTime\Janitor;

use App\User;
use CookieTime\Janitor\Models\Ability;
use CookieTime\Janitor\Models\Role;
use CookieTime\Janitor\Strategy\KeyCode;
use \Illuminate\Foundation\Application;

class Janitor {

    use KeyCode;

    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * check entity has ability
     *
     * @param $entity User| Role
     * @param $ability String
     * @return bool
     */
    public function may($entity, $ability)
    {
        $ability = Ability::where('name', $ability)->first();

        if (empty($ability)) {
            return false;
        }

        $keyCodeSummary = 0;

        if ($entity instanceof User) {
            $roles = $entity->roles()->get()->toArray();
            foreach ($roles as $role) {
                $keyCodeSummary += intval($role['keyCode']);
            }
        } elseif ($entity instanceof Role) {
            $keyCodeSummary = $entity->keyCode;
        } else {
            return false;
        }

        if (intval($ability->keyCode) & $keyCodeSummary) {
            return true;
        }

        return false;
    }

    /**
     * check user has role
     *
     * @param User $user
     * @param $role
     * @return bool
     */
    public function has(User $user, $role)
    {
        $role = $user->roles()->where('name', $role)->first();

        if (empty($role)) {
            return false;
        }

        return true;
    }

    /**
     * Attach Role to User
     * @param User $user
     * @param Role $role
     * @return mixed
     */
    public function attachRole(User $user, Role $role)
    {
        return $user->roles()->attach($role->id);
    }

    /**
     * Attach Ability to User Or Role
     * @param $entity User | Role
     * @param Ability $ability
     * @return mixed
     */
    public function attachAbility($entity, Ability $ability)
    {
        $codes = $this->parseKeyCode($entity->keyCode);

        if (!in_array($ability->keyCode, $codes)) {
            array_push($codes, $ability->keyCode);
        }

        $entity->keyCode = array_sum($codes);

        return $entity->update();
    }


    /**
     * Detach Role to User
     * @param User $user
     * @param Role $role
     * @return mixed
     */
    public function detachRole(User $user, Role $role)
    {
        return $user->roles()->detach($role->id);
    }

    /**
     * Give User Or Role Ability
     *
     * @param $entity
     * @param Ability $ability
     * @return mixed
     */
    public function detachAbility($entity, Ability $ability)
    {
        $codes = $this->parseKeyCode($entity->keyCode);

        if ($key = array_search($ability->keyCode, $codes)) {
            unset($codes[$key]);
        }

        $entity->keyCode = array_sum($codes);

        return $entity->update();
    }

    /**
     * Get Roles of User
     * @param User $user
     * @return mixed
     */
    public function getRoles(User $user)
    {
        return $user->roles()->get()->toArray();
    }

    /**
     * Get Abilities Of User Or Role
     * @param $entity User | Role
     * @return mixed
     */
    public function getAbilities($entity)
    {
        $codes = $this->parseKeyCode($entity->keyCode);

        return Ability::whereIn('keyCode',$codes)->get()->toArray();
    }
}