Yii2 LDAP
==================
Component to use LDAP with Yii2


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require lawiet/yii2-ldap "dev-master"
```

or add

```
"minimum-stability": "dev",
"prefer-stable": true,
require: {
...
    "lawiet/yii2-ldap": "@dev"
...
}
```

to the require section of your `composer.json` file.


Ldap configuration
--------------------

    <?php ....
    'params' => [
        'ldap' => [
            'hostname' => '127.0.0.1',
            'port' => 389,
            //'security' => 'SSL',
            'bind_dn' => false,
            'bind_password' => false,
            'username' => 'admin',
            'password' => 'admin',
            'base_dn' => 'dc=example,dc=org',
            'filter'  => '(&(objectClass=*))',
            'user_base_dn' => 'cn=user,dc=example,dc=org',
            'user_filter' => '(&(objectClass=inetOrgPerson))',
            'group_base_dn' => 'cn=group,dc=example,dc=org',
            'group_filter' => '(&(objectClass=*))',
            'options' => [
                LDAP_OPT_NETWORK_TIMEOUT => 30,
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_REFERRALS => 0,
            ],
        ]
    ]
    ...
    ?>
    


Search the LDAP
---------------

The most basic search as well as the most complex ones are all handled through a unique API. This is the end of the
ldap_read or ldap_list or ldap_search dilemma:

    <?php
    // ... $manager connection & binding

    $results = $manager->search(Search::SCOPE_ALL, 'ou=comp,dc=example,dc=com', '(objectclass=*)');

    // A search result instance is retrieved which provides iteration capability for a convenient use

    foreach ($results as $node) {
        echo $node->getDn();
        foreach ($node->getAttributes() as $attribute) {
            echo sprintf('%s => %s', $attribute->getName(), implode(',', $attribute->getValues()));
        }
    }

SCOPE_ALL will let you search through the whole subtree including the base node with the distinguished name
you gave for the search. Other options are:
- SCOPE_BASE: Will only search for the one node which matches the given distinguished name
- SCOPE_ONE: Will search for nodes just below the one that matches the given distinguished name

Also for more convenience, the component offers a direct method to retrieve one node when you know its
distinguished name:

    <?php
    $node = $manager->getNode('cn=my,ou=node,dc=example,dc=com');

Persist information to the LDAP
-------------------------------

Forget about all the ldap_mod_add, ldap_mod_del, ldap_mod_replace, ldap_add and ldap_delete. The only things you'll
need to remember about now are save() and delete(). The component will track all changes you make on a LDAP entry
and will automagically issue the right function calls for just performing those changes in your directory:

    <?php

    $node = $manager->getNode('cn=node,ou=to,ou=update,dc=example,dc=com');
    $node->get('username')->set('test_user');
    $node->get('objectClass')->add('inetOrgPerson');
    $node->get('sn')->set('Doe');
    $node->removeAttribute('whatever');

    $manager->save($node);

    // Update done

    $node = new Node()
    $node->setDn('ou=create',dc=example,dc=com');
    $node->get('objectClass', true)->add(array('top', 'organizationalUnit'));
    // The true param creates the attribute on the fly
    $node->get('ou', true)->set('create');

    $manager->save($node);

    // New Ldap entry saved

    $manager->delete($node);

    // Now it's gone


See more: tiesa/ldap
