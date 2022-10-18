<?php
    
	class sitemanagement extends ModuleObject {

        /*****************************************
         * @brief 설치시 추가 작업이 필요할시 구현
        ******************************************/
		function moduleInstall() {
			
			return new Object();
        }

        /************************************************
         * @brief 설치가 이상이 없는지 체크하는 method
         ************************************************/
        function checkUpdate() {
		
			// 모듈정보가 없으면 업데이트
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByMid('sitemanagement');
            if(!$module_info) return true;
			
			return false;
        }

        /****************************************************
         * @brief 업데이트 실행
         ****************************************************/
        function moduleUpdate() {
			
			//모듈 생성
			$oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
			$module_info = $oModuleModel->getModuleInfoByMid('sitemanagement');
            if(!$module_info->module_srl) {
				$args = null;
				$args->module = 'sitemanagement';
				$args->mid = 'sitemanagement';
				$args->browser_title = '사이트 운영관리 모듈';
				$oModuleController->insertModule($args);
            }
			
			//트리거 설치 (활동제한기능)
			if(!$oModuleModel->getTrigger('moduleObject.proc', 'sitemanagement', 'controller', 'triggerBeforeModuleObjectProc','before')){
				$oModuleController->insertTrigger('moduleObject.proc', 'sitemanagement', 'controller', 'triggerBeforeModuleObjectProc','before');
			}
			//트리거 설치 (로그인활동안내메세지기능)
			if(!$oModuleModel->getTrigger('member.doLogin', 'sitemanagement', 'controller', 'triggerAfterLogin','after')){
				$oModuleController->insertTrigger('member.doLogin', 'sitemanagement', 'controller', 'triggerAfterLogin','after');
			}
			//트리거 설치 (로그인활동안내메세지기능)
			if(!$oModuleModel->getTrigger('display', 'sitemanagement', 'controller', 'triggerDisplay','before')){
				$oModuleController->insertTrigger('display', 'sitemanagement', 'controller', 'triggerDisplay','before');
			}
			
			//트리거 설치 (로그인활동안내메세지기능)
			if(!$oModuleModel->getTrigger('document.updateReadedCount', 'sitemanagement', 'controller', 'triggerUpdateReadedCountBefore','before')){
				$oModuleController->insertTrigger('document.updateReadedCount', 'sitemanagement', 'controller', 'triggerUpdateReadedCountBefore','before');
			}
			
			return new Object(0, 'success_updated');
        }

        /*****************************************************
         * @brief 캐시 파일 재생성
         *****************************************************/
        function recompileCache() {
        
		}

    }
?>