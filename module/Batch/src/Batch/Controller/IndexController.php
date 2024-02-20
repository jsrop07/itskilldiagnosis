<?php
namespace Batch\Controller;
 
use Zend\Mvc\Controller\AbstractActionController,
    Zend\Console\Request as ConsoleRequest;
use Event\Model\PushModule;

class IndexController extends AbstractActionController
{
 
    public function pushallAction()
    {
        $push_tbl=$this->getServiceLocator()->get('Batch\Model\PushTable');
		$event_tbl=$this->getServiceLocator()->get('EventTable');

        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest){
            throw new \RuntimeException('You can only use this action from a console!');
        }
		$mod = new PushModule();


        $list=$event_tbl->getKoteiPushList();
        if(sizeof($list)>0){
	        foreach($list as $l){
				$res=$mod->send_gcm_notify($l['token'], $l['message']);
				$l['push_result']=$res;
				$event_tbl->setKoteiPushData($l);
	        }
	    }

    }
}
