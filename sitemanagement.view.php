<?php
    
	/**********************************
	**			뷰 클래스			 **
	***********************************/

    class sitemanagementView extends sitemanagement {

        //초기화
        function init() {
            // 사용자 템플릿 파일의 경로 설정 (skins)
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'default';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            $this->setTemplatePath($template_path);
			
			//모듈정보구함
			$args->module = 'sitemanagement'; //쿼리에 모듈명 변수전달
			$oModuleModel = &getModel('module');
			$oSitemanagementModel = &getModel('sitemanagement');
            $this->module_info = $oSitemanagementModel->getModuleInfo($args);
            $this->module_config = $oModuleModel->getModuleConfig('sitemanagement');		
			//모듈정보세팅
			Context::set('module_config', $this->module_config);
            Context::set('module_info', $this->module_info);
        }
		
		//게시물 조회 모니터링
		function dispSitemanagementCheckDocRead(){
			
			//모듈설정 가져옴
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//접근 대상 아닐시 리턴
			$logged_info = Context::get('logged_info');
			if($module_config->docread_access_target == 'user' && !$logged_info) return new Object(-1, '접근할수 없습니다.<br/>관리자에게 문의하세요.');
			if($module_config->docread_access_target == 'customer' && $logged_info) return new Object(-1, '접근할수 없습니다.<br/>관리자에게 문의하세요.');
			
			//노출대상이 회원일때
			if($logged_info && $module_config->docread_access_target == 'user'){
				//회원아이디 일치하지 않을시 리턴
				if($module_config->docread_target_user_id){
					$docread_target_user_id = explode(',',$module_config->docread_target_user_id);
					if(!in_array($logged_info->user_id,$docread_target_user_id)) return new Object(-1, '접근할수 없습니다.<br/>관리자에게 문의하세요.');
				}
				//회원그룹 일치하지 않을시 리턴
				if($module_config->docread_target_group){
					if(!array_intersect($logged_info->group_list,$module_config->docread_target_group)) return new Object(-1, '접근할수 없습니다.<br/>관리자에게 문의하세요.');
				}
			}
			
			//최근 조회기록 가져옴
			$args = new stdClass();
			$args->exclude_module_srls = $module_config->exclude_module_srls;
			$output = executeQueryArray('sitemanagement.docRead_get',$args)->data;
			
			//조회기록 세팅
			Context::set('output', $output);
			
			//브라우저 제목 세팅
			Context::setBrowserTitle('게시물 조회 모니터링');
			
			//템플릿 파일 설정
			$this->setTemplateFile('monitoring_doc_read');
			
		}
	
    }
?>