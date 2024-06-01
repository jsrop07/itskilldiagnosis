<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\Crypt\Password\Bcrypt;

class AccountController extends AbstractActionController
{
	public function indexAction() { header("Location: /admin/login"); exit; }
	
	public function mainAction() {
		$vm = new ViewModel();
		$vm->setTerminal(true);
		$vm->setTemplate("account/login.phtml");
		return $vm;
	}

	/** ログインを処理
	 * @return string die("fail" or "success")
	 */
	public function loginAction() {
		$post = $this->params()->fromPost();
	
		if (isset($post["id"])) {
			$adminTb = $this->getServiceLocator()->get("AdminTable");
			// die($this->Encryption($post["password"]));

			$result = $adminTb->ReadById($post["id"]);
			// ログインを失敗した時
			if (empty($result) || !$this->CheckPassword($post["password"], $result["password"])) {
				die("fail");
			}
			
			// ユーザ情報をセッションに保存
			$session = new Container("user");
			$session->offsetSet("code", $result["code"]);
			$session->offsetSet("name", $result["name"]);
			$session->offsetSet("date_login", $result["date_login"]);
			$session->offsetSet("level", $result["level"]);

			$adminTb->UpdateDateLogin($result["code"]);
			die("success");
		}
	}

	/** ログアウトを処理　*/
	public function logoutAction() {
		$session = new Container("temp");
		$session->getManager()->getStorage()->clear();

		echo "
			<script>
				alert('ログアウトしました。');
				self.location.href = '/admin/login';
			</script>
		";
	}

	/** Encryption password
	 * @param string $password
	 * @return string $Encrypted password
	 */
	function Encryption($password) {
		$bcrypt = new Bcrypt();
		return $bcrypt->create($password);
	}

	/** Compare Encryption password
	 * @param string $password inputpassword
	 * @param string $enPassword encrypted string
	 * @return bool result
	 */
	function CheckPassword($password, $enPassword) {
		$bcrypt = new Bcrypt();
		if ($bcrypt->verify($password, $enPassword)) {
			return true;
		} else {
			return false;
		}
	}
}