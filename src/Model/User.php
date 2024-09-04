<?php
namespace DCO\UserTools\Model;


use DCO\DataTools\Library\ClassesRepository;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Service;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use \Pimcore\Model\DataObject\User as BaseUser;

class User extends BaseUser implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface {

    private $_roles = null;
    public function flushRoles() {
        $this->_roles = null;
        return $this;
    }
    public function getRoles($force = false): array
    {
        if ($this->_roles !== null && !$force)
            return $this->_roles;
        $roles = ['ROLE_USER'];
        foreach ($this->getRelatedObjects() as $o) {
            if (in_array('DCO\UserTools\Interface\UserRoleInterface', class_implements($o))) {
                $roles = array_merge($roles, $o->getRoles());
            }
        }
        $this->_roles = $roles;
        return $roles;
    }

    public function hasRole($role) : bool {
        return in_array($role, $this->getRoles());
    }

    public function eraseCredentials()
    {
        $field = $this->getClass()->getFieldDefinition('password');
        $field->getDataForResource($this->getPassword(), $this);
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    private function getRelatedObjects() {
        $relatedObjects = [];
        foreach (ClassesRepository::getInstalledClassIds() as $classId) {
            $relations = \Pimcore\Db::get()->executeQuery('SELECT `src_id` FROM `object_relations_'.$classId.'` WHERE `dest_id` = :dest_id', ['dest_id' => $this->getId()])->fetchFirstColumn();
            if (!empty($relations)) {
                foreach ($relations as $relationId) {
                    $relatedObjects[] = DataObject::getById($relationId);
                }
            }
        }
        return $relatedObjects;
    }

    public function getProfileImageUrl() : string {
        return '//www.gravatar.com/avatar/'.md5( strtolower( trim(($this->getEmail())))).'.jpg?d=robohash&r=g';
    }

    /**
     * returns the user by e-mail address
     * @param string $email
     * @return User|null
     */
    public static function getUserByEmail(string $email, bool $add = false) : ?User {
        $user = self::getByEmail($email, ['limit' => 1, 'unpublished' => true]);
        if ($user === null && $add) {
            DataObject::setHideUnpublished(false);
            $user = User::createUser(1, $email);
        }
        return $user;
    }

    public static function createUser(DataObject\Folder|DataObject|int $parent, string $email, string $password = null) : User {
        $o = new User();
        $o
            ->setKey($email)
            ->setEmail($email)
            ->setPassword(empty($password) ? md5(rand() * time()) : $password)
            ->setActivationToken(md5(rand() * time()))
            ->setResetToken(null)
            ->setFailedLoginCount(0)
            ->setLastFailedLogin(null)
            ->setLastSuccessfulLogin(null)
            ->setPublished(false);
        if (is_int($parent))
            $o->setParentId($parent);
        else if (is_object($parent))
            $o->setParent($parent);
        $o->save();
        return $o;
    }

    public static function activateUser(string $email, string $activationToken) {
        $user = self::getList([
            'condition' => 'email = '.\Pimcore\Db::get()->quote($email),
            'unpublished' => true,
        ]);
        if ($user->getCount() == 1) {
            $user = $user->current();

            if ($user->getActivationToken() == $activationToken && $user->getFailedLoginCount() < 5) {
                $user
                    ->setPublished(true)
                    ->setActivationToken(null)
                    ->save()
                ;
                return true;
            }
            if ($user->getActivationToken() != $activationToken) {
                $user->setFailedLoginCount($user->getFailedLoginCount()+1)->save();
            }
        }
        return false;
    }

    public function equals(User $user) {
        if (empty($user)) return false;
        return $this->getId() === $user->getId();
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if ($user instanceof User) {
            $isEqual = $user->getUserIdentifier() == $this->getUserIdentifier();
            return $isEqual;
        }

        return false;
    }
}
