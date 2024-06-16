<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\Crypt\Password\Bcrypt;

class ManagerController extends AbstractActionController
{
	function ChkLogin() {
		$session = new Container("user");

		if (!isset($session["code"]) || $session["level"] < 2) {
			echo "
				<script>
					alert('ログインしてくたさい。');
					self.location.href='/admin/login';
				</script>
			";
		}
	}

	public function indexAction() {
		$this->ChkLogin();
		header("Location: ./manager/list");
		exit;
	}

	public function listAction() {
		$this->ChkLogin();
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/13
		*/

		/* 修正前：
		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];
		*/

		/* 修正後： */
		$datas["breadcrumbData"] = ["ITスキル診断管理者管理"];
		/* ここまで */

		$printDataNum = 10;	// Number of data to output on one page

		// Get Current Page
		$page = $this->params()->fromQuery("page", 1);

		// Get datas from AdminTable
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$totalQuestionDatas = $adminTb->ReadAllList();
		$paginationData = $adminTb->GetAllList();

		// Extract output datas and Add numbering
		if (!empty($totalQuestionDatas)) {
			$PrintQuestionDatas = array();
			$startIdx = ($page - 1) * $printDataNum;
			$endIdx = ($page * $printDataNum);
			
			for ($i = 0; $startIdx + $i < $endIdx; $i++) {
				if (!isset($totalQuestionDatas[$startIdx + $i])) break;

				$PrintQuestionDatas[$i] = $totalQuestionDatas[$startIdx + $i];
				$PrintQuestionDatas[$i]["num"] = count($totalQuestionDatas) - ($startIdx + $i);
			}

			$datas["adminDatas"] = $PrintQuestionDatas;
		}

		$vm = $this->SetViewModel($datas, "/manager/manager_list.phtml");
		$vm->noticelist = $paginationData;
		$vm->noticelist->setCurrentPageNumber($page);
		$vm->noticelist->setItemCountPerPage($printDataNum);
		return $vm;
	}

	/** When you click 新規登録 button on 一覧 page */
	public function inputAction() {
		$this->ChkLogin();
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/13
		*/

		/* 修正前：
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "管理者登録"];
		*/

		/* 修正後： */
		$datas["breadcrumbData"] = ["ITスキル診断管理者管理", "管理者登録"];
		/* ここまで */
		$datas["title"] = "管理者登録";

		// Check return from 登録確認　page
		$post = $this->params()->fromPost();
		if (isset($post["id"])) {
			$datas["adminData"] = $post;
		}

		return $this->SetViewModel($datas, "/manager/manager_input.phtml");
	}

	/** When you click 登録 button on 管理者登録 page */
	public function confirmAction() {
		$this->ChkLogin();
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/13
		*/

		/* 修正前：
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "管理者登録" ,"登録確認"];
		*/

		/* 修正後： */
		$datas["breadcrumbData"] = ["ITスキル診断管理者管理", "管理者登録", "登録確認"];
		/* ここまで */
		$datas["title"] = "登録確認";

		$datas["adminData"] = $this->params()->fromPost();

		$adminTb = $this->getServiceLocator()->get("AdminTable");

		return $this->SetViewModel($datas, "/manager/manager_confirm.phtml");
	}

	/** When you choose list data on 管理者一覧 page */
	public function detailAction() {
		$this->ChkLogin();
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/13
		*/

		/* 修正前：
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "管理者詳細"];
		*/

		/* 修正後： */
		$datas["breadcrumbData"] = ["ITスキル診断管理者管理", "管理者詳細"];
		/* ここまで */
		$datas["title"] = "管理者詳細";

		// Get Code
		$code = $this->params()->fromRoute("index");

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		try { $datas["adminData"] = $adminTb->ReadByCode($code); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		return $this->SetViewModel($datas, "/manager/manager_detail.phtml");
	}

	/** When you click 修正 on 管理者詳細 page */
	public function modifyAction() {
		$this->ChkLogin();
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/13
		*/

		/* 修正前：
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "管理者詳細", "管理者修正"];
		*/

		/* 修正後： */
		$datas["breadcrumbData"] = ["ITスキル診断管理者管理", "管理者詳細", "管理者修正"];
		/* ここまで */
		$datas["title"] = "管理者修正";

		$post = $this->params()->fromPost();

		// Check return from 登録確認 page
		if (isset($post["id"])) {
			$datas["adminData"] = $post;
		} else {
			$adminTb = $this->getServiceLocator()->get("AdminTable");
			try { $datas["adminData"] = $adminTb->ReadByCode($post["code"]); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
		}

		return $this->SetViewModel($datas, "/manager/manager_modify.phtml");
	}

	public function createAction() {
		$post = $this->params()->fromPost();

		$adminTb = $this->getServiceLocator()->get("AdminTable");

		// Check Id overlap
		try { $result = $adminTb->ReadById($post["id"]); }
		catch (\Exception $e) { die($e->getMessage()); }
		if (!empty($result)) { die("id overlapped"); }

		// Make Code
		$code = date("y-md");
		$adminDatas = array();
		try { $adminDatas = $adminTb->ReadListByCode($code);}
		catch (\Exception $e) { die($e->getMessage()); }

		// If it keep going, Modify it
		$num = 1;
		if (!empty($adminDatas)) {
			$index = 0;
			while (isset($adminDatas[$index])) {
				// Check empty number
				if ($num != intval(substr($adminDatas[$index]["code"], 7, 3))) {
					break;
				}
				
				$index++; $num++;
			}
		}
		$num = str_pad($num, 3, "0", STR_PAD_LEFT);
		$code .= $num;
		$post["code"] = $code;

		$post["password"] = $this->Encryption($post["password"]);

		// Insert Record
		try { $adminTb->CreateAdmin($post); }
		catch (\Exception $e) { die($e->getMessage()); }

		die("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();

		$adminTb = $this->getServiceLocator()->get("AdminTable");

		// Add password & date_login from Before data
		$adminData = array();
		try { $adminData = $adminTb->ReadByCode($post["code"]); }
		catch (\Exception $e) { die($e->getMessage()); }
		
		$post["date_login"] = $adminData["date_login"];

		// Check Password Reset
		if (empty($post["password"]) || str_replace(" ", "", $post["password"]) == "") {
			$post["password"] = $adminData["password"];
		} else {
			$post["password"] = $this->Encryption($post["password"]);
		}

		// Update Before data to delete
		try { $adminTb->UpdateToDelete($post["idx"]); }
		catch (\Exception $e) { die($e->getMessage()); }
		unset($post["idx"]);

		// Insert Record
		try { $adminTb->CreateAdmin($post); }
		catch (\Exception $e) { die($e->getMessage()); }

		die ("success");
	}

	/** Set Layout & Make ViewModel with datas and template 
	 * @param mixed $datas array #ViewModel($datas)
	 * @param mixed $template string #setTemplate($template) 
	 * @return ViewModel
	*/
	function SetViewModel($datas, $template) {
		$this->layout("layout/default");
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
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