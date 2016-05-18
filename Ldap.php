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
class Ldap extends Component {
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
	 * init() called by yii.
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

	private function _setDn($dn, $_dn, $dn_option){
		$_dn = ($dn_option == 'default') ? $_dn : $dn_option ;
		$dn = ($_dn) ? $dn . ',' . $_dn : $dn ;

		return $dn;
	}

	private function _setFilter($filter, $filter_option){
		$filter = ($filter_option == 'default') ? $filter : $filter_option ;

		return $filter;
	}

	private function _manager(){
		try {
			$tiesaManagerClass = new Manager($this->options, new Driver());
			return $tiesaManagerClass;
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	private function _connect($tiesaManagerClass){
		try {
			$tiesaManagerClass->connect();
			return $tiesaManagerClass;
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

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
	 * _autentication($user, $pass, $dn)
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

	private function _autentication($user = false, $pass = false, $dn = 'default')
	{
		$tiesaManagerClass = $this->_getAutentication($user, $pass, $dn);
		$this->tiesaManagerClass = ($tiesaManagerClass) ? $tiesaManagerClass : false ;
	}

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
	 * getError()
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
		$dn = $this->_setFilter($this->options['user_base_dn'], $dn);
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

			$user = $this->_setDn($user, $this->options['user_base_dn'], $dn);

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

			$user = $this->_setDn($user, $this->options['user_base_dn'], $dn);
			$filter = $this->_setFilter($this->options['user_filter'], $filter);

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

			$group = $this->_setDn($group, $this->options['group_base_dn'], $dn);

			return $this->tiesaManagerClass->getNode($group);
		} catch (Exception $e) {
			$this->ldapError = $e;
			return false;
		}
	}

	/**
	 * autentication($user, $dn, $filter)
	 */
	public function searchGroup($group = 'uid=0', $dn = 'default', $filter = 'default'){
		try {
			if(!$this->tiesaManagerClass)
				$this->_autentication();

			$group = $this->_setDn($group, $this->options['group_base_dn'], $dn);
			$filter = $this->_setFilter($this->options['group_filter'], $filter);

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
