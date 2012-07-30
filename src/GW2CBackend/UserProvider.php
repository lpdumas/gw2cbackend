<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */
 
namespace GW2CBackend;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Silex User Provider for the GW2C-Backend authentification
 *
 * Allows the Silex firewall to know how to load users
 */
class UserProvider implements UserProviderInterface {

    /**
     * Contains the database Silex service
     * @var \GW2CBackend\DatabaseAdapter
     */
    private $conn;

    /**
     * Constructor
     *
     * @param \GW2CBackend\DatabaseAdapter $conn the database Silex service objet
     */
    public function __construct(DatabaseAdapter $conn) {
        $this->conn = $conn;
    }

    /**
     * Loads a user thanks to a username
     *
     * @param string $username the username
     * @return \Symfony\Component\Security\Core\User\User a user object
     */
    public function loadUserByUsername($username) {
        $user = $this->conn->getUserByUsername(strtolower($username));

        if (false === $user || is_null($user)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return new User($user['username'], $user['password'], explode(',', $user['roles']), true, true, true, true);
    }

    /**
     * Refreshes a user
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $user the user object to refresh
     * @return \Symfony\Component\Security\Core\User\User the refreshed user object
     */
    public function refreshUser(UserInterface $user) {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Tells if the class that describes a user is supported or not
     *
     * @param string $class the full-qualified class name
     * @return boolean true if the class is supported, false otherwise
     */
    public function supportsClass($class) {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}