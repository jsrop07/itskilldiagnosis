<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

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
		print_r("Manager Index");
		exit;
	}
	
	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];

		$printDataNum = 10;							// Number of data to output on one page

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

	public function registAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "管理者登録"];
		$datas["title"] = "管理者登録";

		return $this->SetViewModel($datas, "/manager/manager_input.phtml");
	}

	public function detailAction() {
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書詳細"];
		$datas["title"] = "診断書詳細";

		return $this->SetViewModel($datas, "/manager/manager_detail.phtml");
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
}