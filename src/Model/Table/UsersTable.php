<?php
namespace App\Model\Table;

use App\Model\Entity\User;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Event\Event;
use Cake\ORM\Entity;


/**
 * Users Model
 *
 */
class UsersTable extends Table
{

    private $_hasher;
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('users');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
        $this->belongsTo('Profiles');

        $this->_hasher = new DefaultPasswordHasher();

    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmpty('email');

        $validator
            ->requirePresence('profile_id', 'create')
            ->notEmpty('profile_id');

        $validator
            ->add('password', 'custom', [
                'rule' => [$this, 'currentPassword'],
                'message' => 'Senha incorreta.'
            ]);

        $validator
            ->notEmpty('newPassword', 'Preencha sua nova senha')
            ->add('newPassword', 'custom',[
                'rule' => [$this, 'newPassNotSame'],
                'message' => 'Sua nova senha não pode ser igual a senha antiga'
            ]);

        $validator
            ->notEmpty('passwordConfirm', 'Confirme sua nova senha')
            ->add('passwordConfirm', 'custom', [
                'rule' => [$this, 'passwordConfirm'],
                'message' => 'Senha de Confirmação não confere'
            ]);
        

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *  
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['email']));
        return $rules;
    }

    public function lastLogin( $user_id )
    {
        
        $user = $this->get($user_id); // Return article with id 12

        $user->last_login = date('Y-m-d H:i:s');
        $this->save($user);
    }

    
    public function currentPassword( $check, $context)
    {
        $user = $this->get($context['data']['id']);

        return $this->_hasher->check($check, $user->password);
    }

    
    public function newPassNotSame( $check, $context)
    {   
        $user = $this->get($context['data']['id']);

        if ( isset( $user->pass_switched ) ) {
            
            $currentPassword = $user->password;

            if ($this->_hasher->check($check, $currentPassword)){
                return false;
            }
        }
         
        return true;
    }

    public function passwordConfirm($check, $context)
    {
        return $context['data']['passwordConfirm'] == $context['data']['newPassword'];
    }

    // public static function isAdmin( $profile_id = null )
    // {

    //     if( !$profile_id )
    //         $profile_id = AuthComponent::user( 'profile_id' );

    //     return $profile_id == Configure::read( 'AdminProfileId' );
    // }

    // public static function isAdminUser( $user_id = null )
    // {

    //     if( !$user_id )
    //         $user_id = AuthComponent::user( 'id' );

    //     return $user_id == Configure::read( 'AdminUserId' );
    // }

    // ----------------------------------------
    //  * Callbacks
    //  ----------------------------------------

    // public function beforeValidate( $options = array() )
    // {

    //     if( array_key_exists( 'pass_switched', $options ) ){

    //         $this->_passSwitched = $options[ 'pass_switched' ];

    //         if( !$this->_passSwitched ){

    //             $this->validate[ 'newPassword' ][ 'alphanumeric' ][ 'allowEmpty' ] = false;
    //             $this->validate[ 'newPassword' ][ 'between' ][ 'allowEmpty' ] = false;
    //         }
    //     }

    //     return true;
    // }
    
    // public function beforeSave( $options = array() )
    // {

    //     if ( !$this->id )
    //         $this->data[ $this->name ][ 'password' ] = AuthComponent::password( '123456' );

    //     elseif ( !empty( $this->data[ $this->name ][ 'newPassword' ] ) ) {

    //         $this->data[ $this->name ][ 'password' ] = AuthComponent::password( $this->data[ $this->name ][ 'newPassword' ] );
    //         unset( $this->data[ $this->name ][ 'newPassword' ] );

    //         if( !empty( $this->data[ $this->name ][ 'passwordConfirm' ] ) )
    //             unset( $this->data[ $this->name ][ 'passwordConfirm' ] );

    //         if( isset( $this->_passSwitched ) )
    //             if( !$this->_passSwitched )
    //                 $this->_passSwitched = $this->data[ $this->name ][ 'pass_switched' ] = '1';

    //     } elseif ( isset( $this->data[ $this->name ][ 'password' ] ) )
    //         unset( $this->data[ $this->name ][ 'password' ] );

    //     if ( isset( $this->data[ $this->name ][ 'pass_switched' ] ) )
    //         if( !$this->data[ $this->name ][ 'pass_switched' ] )
    //             unset( $this->data[ $this->name ][ 'pass_switched' ] );
        
    //     return true;
    // }

    public function beforeSave(Event $event, Entity $entity) 
    {   
        if ($entity->isNew()) {
            $entity->password = '123456'; 
        } elseif (!empty($entity->newPassword)) {
            $entity->password = $entity->newPassword;

            if (isset($entity->pass_switched)){
                if (!$entity->pass_switched){
                    $entity->pass_switched = $entity->pass_switched = '1';
                }
            }
        } elseif (isset($entity->password)) {
            unset($entity->password);
        }

        if (isset($entity->pass_switched)) {
            if (!$entity->pass_switched) {
                unset($entity->pass_switched);
            }
        }
        
    }   

}

