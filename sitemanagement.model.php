<?php
    
	/**********************************
	**			모델 클래스			 **
	***********************************/
	
    class sitemanagementModel extends sitemanagement {

        //초기화
        function init() {
		
		}

		//모듈정보구함
		function getModuleInfo($args){
			$output = executeQuery('sitemanagement.get_moduleInfo',$args);
            if(!$output->data->module_srl) return;
			$oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($output->data->module_srl);
			return $module_info;
        }
	
	}
?>