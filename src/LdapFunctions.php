<?php
/**
 * @copyright Copyright &copy; Jorge Gonzalez, 2016
 * @package yii2-ldap
 * @version 1.0.0
 */
namespace lawiet\ldap\src;

use yii\base\Component;
use Toyota\Component\Ldap\API as API;
use Toyota\Component\Ldap\Core as Core;
use Toyota\Component\Ldap\Exception as Exception;
use Toyota\Component\Ldap\Platform\Native as Platform;
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
    protected $tiesaManagerClass = null;

    protected $ldapError = false;

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
    protected $default = [
            'hostname' => '127.0.0.1',
            'port' => 389,
            'bind_dn' => false,
            'bind_password' => false,
            'username' => 'admin',
            'password' => 'admin',
            'base_dn' => 'dc=example,dc=org',
            'filter'  => '(&(objectClass=*))',
            'user_options' => [
                'base_dn' => 'cn=user,dc=example,dc=org',
                'filter' => '(&(objectClass=inetOrgPerson))',
                'name_attribute' => 'uid',
                'attributes' => '*',
            ],
            'group_options' => [
                'base_dn' => 'cn=group,dc=example,dc=org',
                'filter' => '(&(objectClass=*))',
                'name_attribute' => 'uid',
                'attributes' => '*',
            ],
            'options' => [
                LDAP_OPT_NETWORK_TIMEOUT => 30,
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_REFERRALS => 0,
            ],
        ];

    /**
     * _initializate().
     */
    protected function _initializate()
    {
        try {
            $this->options = $this->_setDefault($this->default, $this->options);

            $user = isset($this->options['username']) ? $this->options['username'] : (isset($this->options['bind_dn']) ? $this->options['bind_dn'] : false );
            $password = isset($this->options['password']) ? $this->options['password'] : (isset($this->options['bind_password']) ? $this->options['bind_password'] : false );
            $this->_autentication($user, $password);
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _setDefault($getDefault, $setOptions).
     */
    protected function _setDefault($getDefault, $setOptions){
        foreach ($setOptions as $key => $value){
            if(isset($setOptions[$key])){
                if(count($setOptions[$key])>0){
                    if(is_array($value))
                        $getDefault[$key] = $this->_setDefault($getDefault[$key], $setOptions[$key]);
                    else
                        $getDefault[$key] = $setOptions[$key];
                }else{
                    unset($setOptions[$key]);
                }
            }
        }

        return $getDefault;
    }

    /**
     * _setDn($dn, $_dn, $dn_option).
     */
    protected function _setDn($dn, $_dn, $dn_option){
        $_dn = ($dn_option == 'default') ? $_dn : $dn_option ;
        $dn = ($_dn) ? $dn . ',' . $_dn : $dn ;

        return $dn;
    }

    /**
     * _getDefault($default, $default_option).
     */
    protected function _getDefault($default, $default_option){
        $default = ($default_option == 'default') ? $default : $default_option ;

        return $default;
    }

    /**
     * _manager().
     */
    protected function _manager(){
        try {
            $tiesaManagerClass = new Core\Manager($this->options, new Platform\Driver());
            return $tiesaManagerClass;
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _connect($tiesaManagerClass).
     */
    protected function _connect($tiesaManagerClass){
        try {
            $tiesaManagerClass->connect();
            return $tiesaManagerClass;
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _bind($tiesaManagerClass, $user, $pass, $dn).
     */
    protected function _bind($tiesaManagerClass, $user = false, $pass = false, $dn = 'default'){
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
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _newNode($node).
     */
    protected function _setInNode($node = null, $object=null, $value){
        try {
            if(is_callable([$node, 'has'], true, $nombre_a_llamar))
                if($node->has($object)){
                    $attribute = $node->get($object)->getValues();
                    if(is_array($value)){
                        if(count($object)>1)
                            $value = array_merge($attribute, $value);

                        $node->get($object)->add($value);
                    }else{
                        $node->get($object)->set($value);
                    }
                }else{
                    if(is_array($value))
                        $node->get($object, true)->add($value);
                    else
                        $node->get($object, true)->set($value);
                }
            else{
                $this->ldapError = 'Nodo fallido';
                return $node;
            }

            return $node;
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _newNode($node).
     */
    protected function _newNode($dn = false){
        try {
            if(!$this->tiesaManagerClass)
                $this->_autentication();

            $node = $this->_getNode($dn);

            if($node){
                $node->tracker->markOverridden();

                return $node;
            }else{
                $node = new Core\Node();
                $node->setDn($dn);
                $node->tracker->markOverridden();

                return $node;
            }
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _getNode($node).
     */
    protected function _getNode($node = false){
        try {
            if(!$this->tiesaManagerClass)
                $this->_autentication();

            return $this->tiesaManagerClass->getNode($node);
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _getNode($user, $filter).
     */
    protected function _search($user = false, $filter){
        try {
            if(!$this->tiesaManagerClass)
                $this->_autentication();

            return $this->tiesaManagerClass->search(Search::SCOPE_ALL, $user, $filter);
        } catch (\Exception $e) {
            $this->ldapError = $e;
            return false;
        }
    }

    /**
     * _getAutentication($user, $pass, $dn)
     */
    protected function _getAutentication($user = false, $pass = false, $dn = 'default')
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
    protected function _autentication($user = false, $pass = false, $dn = 'default')
    {
        $tiesaManagerClass = $this->_getAutentication($user, $pass, $dn);
        $this->tiesaManagerClass = ($tiesaManagerClass) ? $tiesaManagerClass : false ;
    }
}
