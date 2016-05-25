<?php

/***********************************************************************************
 * X2CRM is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2016 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. on our website at www.x2crm.com, or at our
 * email address: contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 **********************************************************************************/


class UserSessionTokenTest extends X2DbTestCase {

    public static function referenceFixtures(){
        return array('user' => 'User');
    }
    
    public $fixtures = array(
        'tokens' => 'SessionToken'
    );

    public function __construct($name = NULL, array $data = array(), $dataName = ''){
        parent::__construct($name, $data, $dataName);
    }

    public function testAuthenticate() {
        // Test that deleting expired tokens works and that invalid tokens return false:
        
        $oldTokenCount = SessionToken::model()->count();
        $newTestForm = new LoginForm;
        $this->assertFalse($newTestForm->loginSessionToken(),'should have returned false; token id/data is invalid');
        
        $expiredId = $this->tokens('testUser_expired')->id;
        Yii::app()->request->cookies['sessionToken'] = new CHttpCookie('sessionToken', $expiredId);
        $newTestForm->loginSessionToken();
        $this->assertFalse(X2Model::model('SessionToken')->findByPk($expiredId),'Expired token should get deleted upon call to LoginForm::login(...)');
        //Test if more than one record got deleted
        //$this->assertEquals($oldTokenCount-1,SessionToken::model()->count(),'More than one record got deleted. This should not happen according to the fixture data.');
        // Deleted user:
        $nonExistentId = $this->tokens('testNotExistsUser')->id;
        Yii::app()->request->cookies['sessionToken'] = new CHttpCookie('sessionToken', $nonExistentId);
        $this->assertFalse($newTestForm->loginSessionToken(),'should have returned false; user does not exist');
        $this->assertFalse(User::model()->findByAlias($nonExistentId),'token corresponding to nonexistent user should have been deleted');
        // Deactivated user:
        $deactivatedId = $this->tokens('testDeactivatedUser')->id;
        Yii::app()->request->cookies['sessionToken'] = new CHttpCookie('sessionToken', $deactivatedId);
        $this->assertFalse($newTestForm->loginSessionToken(),'should have returned false; user has been deactivated');
        $this->assertFalse(User::model()->findByAlias($deactivatedId), 'token corresponding to deactivated user should have been deleted');
        // User logged in multiple times:
        //$expiredId = $this->tokens('testBruteforceUser')->id;
        //$this->assertFalse(LoginForm::loginSessionToken(),'should have returned false; wrong token key');
        //$this->assertGreaterThan(-5,$this->tokens('testDeactivatedUser')->token);
        // Correct token:
        $validId = $this->tokens('testWorkingUser')->id;
        Yii::app()->request->cookies['sessionToken'] = new CHttpCookie('sessionToken', $validId);
        $this->assertTrue($newTestForm->loginSessionToken(),'should have returned true; correct session token, valid user');

    }

    public function testGenerate() {
        $sessionIdToken = session_id();
        $sessionToken = new SessionToken;
        $sessionToken->id = $sessionIdToken;
        $sessionToken->user = 'testuser';
        $sessionToken->lastUpdated = time();
        $sessionToken->status = 0;
        $sessionToken->IP = '1.0.0.0';
        $this->assertTrue((bool) $sessionToken->save());
        $sessionModel = X2Model::model('SessionToken')->findByPk($sessionIdToken); 
        $this->assertEquals($sessionToken->user,$sessionModel->user);
        $user = User::model()->findByAlias($sessionToken->user);
        $this->assertInstanceOf('User', $user);
        $this->assertEquals($sessionToken->user,$user->username);
        $this->assertEquals($sessionModel->user,$user->username);
    }
}

?>