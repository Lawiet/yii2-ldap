<?php
/**
 * @copyright Copyright &copy; Jorge Gonzalez, 2016
 * @package yii2-ldap
 * @version 1.0.0
 */
namespace lawiet\ldap;

use yii;
use lawiet\ldap\src\LdapFunctions;
use Toyota\Component\Ldap\API\SearchInterface as Search;

/**
 * Component for ldap
 *
 * @author Jorge Gonzalez <ljorgelgonzalez@gmail.com>
 * @since 1.0
 *
 * Documentation: https://packagist.org/packages/tiesa/ldap
 */
class Ldap extends LdapFunctions {

	/**
	 * init() called by yii.
	 */
	public function init()
	{
		try {
			$this->options = count($this->options) > 0 ? $this->options : Yii::$app->params['ldap'];
			$this->_initializate();
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * getError()
	 */
	public function getError()
	{
		return $this->ldapError;
	}

    /**
     * getOptions()
     */
    public function getOptions($index)
    {
        return $this->options[$index];
    }

	/**
	 * generatePassword($password, $encode)
	 */
	public static function generatePassword($password = '', $encode = 'ssha') {
		$return = '';
		switch (strtolower(trim($encode))) {
			//case 'crypt':
			//	eval('mt_srand((double)microtime()*1000000); $salt = pack(\'CCCC\', mt_rand(), mt_rand(), mt_rand(), mt_rand()); $return = \'{CRYPT}\' . crypt($password, $salt);');
			//break;
			case 'md5':
				eval('$return = \'{MD5}\' . base64_encode(pack("H*", md5($password)));');
			break;
			case 'sha1':
				eval('$return = \'{SHA}\' . base64_encode(pack("H*", sha1($password)));');
			break;
			default:
				eval('mt_srand((double)microtime()*1000000); $salt = pack(\'CCCC\', mt_rand(), mt_rand(), mt_rand(), mt_rand()); $return = \'{SSHA}\' . base64_encode(pack("H*", sha1($password . $salt)) . $salt);');
			break;
		}
		return $return;
	}

	/**
	 * autentication($user, $pass, $dn)
	 */
	public function autentication($user = false, $pass = false, $dn = 'default')
	{
        $options = $this->options['user_options'];
		$dn = $this->_getDefault($options['base_dn'], $dn);
		$tiesaManagerClass = $this->_getAutentication($user, $pass, $dn);

		return ($tiesaManagerClass) ? true : false ;
	}

	/**
	 * getUser($user, $dn)
	 */
	public function getUser($user = 'uid=0', $dn = 'default'){
		try {
			if(!$this->tiesaManagerClass)
				$this->_autentication();

             $options = $this->options['user_options'];
			$user = $this->_setDn($user, $options['base_dn'], $dn);

			return $this->tiesaManagerClass->getNode($user);
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * searchUser($user, $dn, $filter)
	 */
	public function searchUser($user = 'uid=0', $dn = 'default', $filter = 'default'){
		try {
			if(!$this->tiesaManagerClass)
				$this->_autentication();

            $options = $this->options['user_options'];
			$user = $this->_setDn($user, $options['base_dn'], $dn);
			$filter = $this->_getDefault($options['filter'], $filter);

			return $this->tiesaManagerClass->search(Search::SCOPE_ALL, $user, $filter);
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * getGroup($user, $dn)
	 */
	public function getGroup($group = 'uid=0', $dn = 'default'){
		try {
			if(!$this->tiesaManagerClass)
				$this->_autentication();

            $options = $this->options['group_options'];
			$group = $this->_setDn($group, $options['base_dn'], $dn);

			return $this->tiesaManagerClass->getNode($group);
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

    /**
     * searchGroup($group, $dn, $filter)
     */
    public function searchGroup($group = 'uid=0', $dn = 'default', $filter = 'default'){
        try {
            if(!$this->tiesaManagerClass)
                $this->_autentication();

            $options = $this->options['group_options'];
            $group = $this->_setDn($group, $options['base_dn'], $dn);
            $filter = $this->_getDefault($options['filter'], $filter);

            return $this->tiesaManagerClass->search(Search::SCOPE_ALL, $group, $filter);
        } catch (Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

	/**
	 * Use magic PHP function __call to route function calls to the tiesaldap class.
	 * Look into the tiesaldap class for possible functions.
	 *
	 * @param string $methodName Method name from tiesaldap class
	 * @param array $methodParams Parameters pass to method
	 * @return mixed
	 */
	public function __call($methodName, $methodParams)
	{
		if (method_exists($this->tiesaManagerClass, $methodName)) {
			return call_user_func_array(array($this->tiesaManagerClass, $methodName), $methodParams);
		} else {
			return parent::__call($methodName, $methodParams);
		}
	}

}
