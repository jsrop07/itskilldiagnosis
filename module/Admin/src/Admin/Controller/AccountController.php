<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

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

			$result = $adminTb->ReadById($post["id"]);
			// ログインを失敗した時
			if (empty($result) || $result["password"] != $post["password"]) {
				die("fail");
			}
			
			// ユーザ情報をセッションに保存
			$session = new Container("user");
			$session->offsetSet("code", $result["code"]);
			$session->offsetSet("name", $result["name"]);
			$session->offsetSet("date_login", $result["date_login"]);

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
}