<?php
/**
 * @copyright Copyright &copy; Jorge Gonzalez, 2016
 * @package yii2-ldap
 * @version 1.0.0
 */
namespace lawiet\ldap;

use yii;
use yii\base\Component;
use Toyota\Component\Ldap\Core\Manager;
use Toyota\Component\Ldap\Platform\Native\Driver;
use Toyota\Component\Ldap\API\SearchInterface as Search;

/**
 * Component for ldap
 *
 * @author Jorge Gonzalez <ljorgelgonzalez@gmail.com>
 * @since 1.0
 *
 * Documentation: https://packagist.org/packages/tiesa/ldap
 */
class LdapFunctions extends Component {
	/**
	 * The internal tiesaManager object.
	 *
	 * @var object tiesaManager
	 */
	private $tiesaManagerClass = null;

	private $ldapError = false;

	/**
	 * Options variable for the tiesaldap module.
	 * See tiesaldap __construct function for possible values.
	 *
	 * @var array Array with option values
	 */
	public $options = [];

	/**
	 * Options values default.
	 */
	private $default = [
			'hostname' => '127.0.0.1',
			'port' => 389,
			'bind_dn' => false,
			'bind_password' => false,
			'username' => 'admin',
			'password' => 'admin',
			'base_dn' => 'dc=example,dc=org',
			'filter'  => '(&(objectClass=*))',
			'user_base_dn' => 'cn=user,dc=example,dc=org',
			'user_filter' => '(&(objectClass=inetOrgPerson))',
			'user_name_attribute' => 'uid',
			'user_attributes' => '*',
			'group_base_dn' => 'cn=group,dc=example,dc=org',
			'group_filter' => '(&(objectClass=*))',
			'group_name_attribute' => 'uid',
			'group_user_attribute' => '*',
			'options' => [
				LDAP_OPT_NETWORK_TIMEOUT => 30,
				LDAP_OPT_PROTOCOL_VERSION => 3,
				LDAP_OPT_REFERRALS => 0,
			],
		];

	/**
	 * _initializate().
	 */
	private function _initializate()
	{
		try {
			$this->options = $this->_setDefault($this->default, $this->options);

			$user = isset($this->options['username']) ? $this->options['username'] : (isset($this->options['bind_dn']) ? $this->options['bind_dn'] : false );
			$password = isset($this->options['password']) ? $this->options['password'] : (isset($this->options['bind_password']) ? $this->options['bind_password'] : false );
			$this->_autentication($user, $password);
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * _setDefault($getDefault, $setOptions).
	 */
	private function _setDefault($getDefault, $setOptions){
		foreach ($setOptions as $key => $value){
			if(isset($setOptions[$key])){
				if(count($setOptions[$key])>0){
					if(is_array($value)){
						$getDefault[$key] = array_merge($getDefault[$key], $setOptions[$key]);
					}
				}else
					unset($setOptions[$key]);
			}
		}

		$getDefault = array_merge($getDefault, $setOptions);

		return $getDefault;
	}

	/**
	 * _setDn($dn, $_dn, $dn_option).
	 */
	private function _setDn($dn, $_dn, $dn_option){
		$_dn = ($dn_option == 'default') ? $_dn : $dn_option ;
		$dn = ($_dn) ? $dn . ',' . $_dn : $dn ;

		return $dn;
	}

	/**
	 * _setFilter($filter, $filter_option).
	 */
	private function _setFilter($filter, $filter_option){
		$filter = ($filter_option == 'default') ? $filter : $filter_option ;

		return $filter;
	}

	/**
	 * _getAutentication().
	 */
	private function _getAutentication(){
		try {
			$tiesaManagerClass = new Manager($this->options, new Driver());
			return $tiesaManagerClass;
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * _connect($tiesaManagerClass).
	 */
	private function _connect($tiesaManagerClass){
		try {
			$tiesaManagerClass->connect();
			return $tiesaManagerClass;
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * _bind($tiesaManagerClass, $user, $pass, $dn).
	 */
	private function _bind($tiesaManagerClass, $user = false, $pass = false, $dn = 'default'){
		try {
			if($user && $pass){
				$user = $this->_setDn($user, $this->options['base_dn'], $dn);
				$tiesaManagerClass->bind($user, $pass);
			}else if($this->options['bind_dn'] && $this->options['bind_password']){
				$user = $this->_setDn($this->options['bind_dn'], $this->options['base_dn'], $dn);
				$tiesaManagerClass->bind($user, $this->options['bind_password']);
			}else if($this->options['username'] && $this->options['password']){
				$user = $this->_setDn($this->options['username'], $this->options['base_dn'], $dn);
				$tiesaManagerClass->bind($user, $this->options['password']);
			}else
				$tiesaManagerClass->bind();

			return $tiesaManagerClass;
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * _getAutentication($user, $pass, $dn)
	 */
	private function _getAutentication($user = false, $pass = false, $dn = 'default')
	{
		$tiesaManagerClass = $this->_manager();

		if($tiesaManagerClass)
			$this->_connect($tiesaManagerClass);

		if($tiesaManagerClass)
			$this->_bind($tiesaManagerClass, $user, $pass, $dn);

		return $tiesaManagerClass;
	}

	/**
	 * _autentication($user, $pass, $dn).
	 */
	private function _autentication($user = false, $pass = false, $dn = 'default')
	{
		$tiesaManagerClass = $this->_getAutentication($user, $pass, $dn);
		$this->tiesaManagerClass = ($tiesaManagerClass) ? $tiesaManagerClass : false ;
	}
}