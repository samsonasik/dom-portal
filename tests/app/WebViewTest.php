<?php

namespace App\Database;

use App\Controllers\Home;
use App\Controllers\User;
use App\Entities\Host;
use App\Entities\Login;
use App\Libraries\SendGridEmail;
use App\Libraries\VirtualMinShell;
use App\Models\HostModel;
use App\Models\LoginModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIDatabaseTestCase;
use Config\Services;

class WebViewTest extends CIDatabaseTestCase
{
    protected $namespace  = null;

    public function testStaticView()
    {
        ($home = new Home())->initController($req = Services::request(), Services::response(), Services::logger());
        $this->assertTrue($home->index()->getStatusCode() === 302);
        $this->assertTrue($home->import()->getStatusCode() === 302);
        $this->assertTrue(is_string($home->login()));
        $this->assertTrue(is_string($home->register()));
        $this->assertTrue(is_string($home->forgot()));
        try {
            $this->assertEmpty($home->notify());
        } catch (\Throwable $th) {
            $this->assertTrue($th instanceof PageNotFoundException);
        }
        try {
            $this->assertEmpty($home->forgot_reset());
        } catch (\Throwable $th) {
            $this->assertTrue($th instanceof PageNotFoundException);
        }
        try {
            $this->assertEmpty($home->verify());
        } catch (\Throwable $th) {
            $this->assertTrue($th instanceof PageNotFoundException);
        }
    }

    public function testUserView()
    {
        (new LoginModel())->register([
            'name' => 'Contoso User',
            'email' => 'contoso@example.com',
            'password' => 'mycontosouser',
        ], true, true);

        // profile page
        ($user = new User())->initController($req = Services::request(), Services::response(), Services::logger());
        $this->assertTrue(is_string($user->profile()));

        // server page
        $this->assertTrue($user->status()->getStatusCode() === 302);
        $req->setGlobal('get', ['server' => 1]);
        $this->assertTrue(is_string($user->status()));

        // hosts page
        (new HostModel())->insert([
            'id' => 1,
            'login_id' => 1,
            'plan_id' => 1,
            'server_id' => 1,
            'username' => 'contoso',
            'domain' => 'contoso.dom.my.id',
            'expiry_at' => date('Y-m-d H:i:s'),
        ]);
        $req->setGlobal('get', []);
        $this->assertTrue(is_string($user->host('list')));
        // $this->assertTrue(is_string($user->host('create')));
        foreach ([
            'detail', 'see', 'nginx', 'invoices', // 'upgrade',
            'dns', 'ssl', 'rename', 'cname', 'delete', 'deploys',
        ] as $page) {
            $this->assertTrue(is_string($user->host($page, 1)));
        }
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->db->resetDataCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $req = Services::request();
        $req->setMethod('get');
        $req->setGlobal('get', []);
        $req->setGlobal('post', []);
        $req->setGlobal('request', []);
    }
}
