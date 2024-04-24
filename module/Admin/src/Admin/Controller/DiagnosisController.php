<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class DiagnosisController extends AbstractActionController
{
	public function indexAction() { print_r("Diagnosis Index"); exit; }
	
	public function listAction() {
		$datas["breadcrumbData"] = ["ITスキル診断書管理"];

		$query = $this->params()->fromQuery();
		unset($query["page"]);

		$page = $this->params()->fromQuery("page", 1);
		$printDataNum = 10;
		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$totalQuestionDatas = iterator_to_array($questionTb->ReadAllList());
		$paginationData = $questionTb->GetAllList();

		$datas["totalData"] = count($totalQuestionDatas);

		$vm = $this->SetViewModel($datas, "/diagnosis/diagnosis_list.phtml");
		
		$vm->noticelist = $paginationData;
		$vm->noticelist->setCurrentPageNumber($page);
		$vm->noticelist->setItemCountPerPage($printDataNum);

		return $vm;
	}

	public function registAction() {
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書登録"];
		$datas["title"] = "診断書登録";

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_input.phtml");
	}

	public function detailAction() {
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書詳細"];
		$datas["title"] = "診断書詳細";

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_detail.phtml");
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