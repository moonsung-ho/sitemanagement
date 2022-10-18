<?php
    
	/**********************************
	**		관리자 뷰 클래스		 **
	***********************************/

    class sitemanagementAdminView extends sitemanagement {

        //초기화
        function init() {
			//모듈정보구함
			$args->module = 'sitemanagement'; //쿼리에 모듈명 변수전달
			$oModuleModel = &getModel('module');
			$oSitemanagementModel = &getModel('sitemanagement');
            $this->module_info = $oSitemanagementModel->getModuleInfo($args);
            $this->module_config = $oModuleModel->getModuleConfig('sitemanagement');		
			//모듈정보세팅
			Context::set('module_config', $this->module_config);
            Context::set('module_info', $this->module_info);
			// 관리자 템플릿 파일의 경로 설정 (tpl)
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

		//스킨관리 
		function dispSitemanagementAdminSkinInfo() {
			$oModuleAdminModel = &getAdminModel('module');
			$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
			Context::set('skin_content', $skin_content);
			// 템플릿 파일 지정			
			$this->setTemplateFile('skin_info');
        }	
		
		//권한관리
		function dispSitemanagementAdminGrantInfo() {
			$oModuleAdminModel = &getAdminModel('module');
			$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
			Context::set('grant_content', $grant_content);
			//템플릿 파일 지정
			$this->setTemplateFile('grant_list');
		}
		
        //관리자모듈설정
        function dispSitemanagementAdminModuleInfo() {
			// 모듈 카테고리 목록 구함
			$oModuleModel = &getModel('module');
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);
			// 스킨 목록 구함
            $skin_list = $oModuleModel->getSkins($this->module_path);
			$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
            Context::set('skin_list',$skin_list);
			Context::set('mskin_list',$mskin_list);
			// 레이아웃 목록 구함
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
			$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
            Context::set('layout_list', $layout_list);
			Context::set('mlayout_list', $mobile_layout_list);
			//템플릿 파일 지정
            $this->setTemplateFile('index');
		}
		
		//활동제한설정
        function dispSitemanagementAdminLimitAccessConfig() {
			//모듈정보 구함
			$args->module = 'sitemanagement';
			$oSitemanagementModel = &getModel('sitemanagement');
            $module_info = $oSitemanagementModel->getModuleInfo($args);
			Context::set('module_info', $module_info);
			
			//포인트 모듈 정보구함
			$oModuleModel = &getModel('module');
			$point_config = $oModuleModel->getModuleConfig('point');
			Context::set('point_config', $point_config);
			Context::set('max_level', $point_config->max_level);
		
			//mid 리스트 구함
			$columnList = array('module_srl', 'mid', 'browser_title', 'module');
			$mid_list = $oModuleModel->getMidList(null, $columnList);
			Context::set('mid_list', $mid_list);
			
			//모듈부분설정 구함
			$module_part_config = $oModuleModel->getModulePartConfigs('sitemanagement');
			Context::set('module_part_config', $module_part_config);
			
			//템플릿 파일 지정
            $this->setTemplateFile('limit_access_config');
		}
		
		//유령회원정리
        function dispSitemanagementAdminGhostMember() {
			//필터링값 모두 받음
            $args = Context::getRequestVars();
			
			//필터값 없을시 리턴
			if(!$args->join_date && !$args->absence_date && !$args->target_date && !$args->doc_count && !$args->com_count) return $this->setTemplateFile('ghost_member');
				
			//유령회원필터1 (가입일,미접속)
			if($args->join_date) $args->join_date = date('Ymd',strtotime(sprintf('-%s days', $args->join_date)));
			if($args->absence_date) $args->absence_date = date('Ymd',strtotime(sprintf('-%s days', $args->absence_date)));
			$filter_result = executeQueryArray('sitemanagement.getGhostMemberFilter1',$args)->data;
			
			//유령회원필터2 (게시글,댓글)
			if($filter_result){
				if($args->target_date) $args->target_date = date('Ymd',strtotime(sprintf('-%s days', $args->target_date)));
				foreach($filter_result as $key => $val){
					$args->member_srl = $val->member_srl;
					$val->doc_count = executeQuery('sitemanagement.getGhostMemberFilter2',$args)->data->doc_count;
					$val->com_count = executeQuery('sitemanagement.getGhostMemberFilter3',$args)->data->com_count;
				}
			}
			
			//유령회원필터3 (레벨)
			if($filter_result && $args->member_level){
				$oPointModel = &getModel('point'); 
				$oModuleModel = &getModel('module');
				$point_config = $oModuleModel->getModuleConfig('point'); 
				foreach($filter_result as $key => $val){
					$point = $oPointModel->getPoint($val->member_srl); 
					$val->member_level = $oPointModel->getLevel($point, $point_config->level_step); 
				}
			}
			
			//결과취합
			if($filter_result){
				foreach($filter_result as $key => $val){
					if($args->doc_count && $args->doc_count <= $val->doc_count) unset($filter_result[$key]);
					if($args->com_count && $args->com_count <= $val->com_count) unset($filter_result[$key]);
					if($args->member_level && $args->member_level <= $val->member_level) unset($filter_result[$key]);
				}
			}
			
			//필터결과 세팅
			Context::set('result', $filter_result);
			Context::set('member_list_count', count($filter_result));
			
			//템플릿 파일 지정
            $this->setTemplateFile('ghost_member');
		}
		
		//비활성게시물정리
        function dispSitemanagementAdminCleanDocument() {
			//mid 리스트 구함
			$oModuleModel = &getModel('module');
			$columnList = array('module_srl', 'mid', 'browser_title', 'module');
			$mid_list = $oModuleModel->getMidList(null, $columnList);
			Context::set('mid_list', $mid_list);
			
			//모듈설정구함
			$oModuleModel = &getModel('module');
            $module_config = $oModuleModel->getModuleConfig('sitemanagement');	
			
			//필터링값 모두 받음
            $args = Context::getRequestVars();
			
			//게시물 찌꺼기 조회시 조건문 종료후 리턴
			if($args->trach_doc){
				$target_module_srls_not = array();
				foreach($mid_list as $key => $val){
					array_push($target_module_srls_not, $val->module_srl);
				}
				$args = null;
				$args->target_module_srls_not = $target_module_srls_not;
				$filter_result = executeQueryArray('sitemanagement.getCleanDocumentFilter1',$args)->data;
				
				//필터결과 세팅
				Context::set('result', $filter_result);
				Context::set('document_list_count', count($filter_result));
				
				//템플릿 파일 지정
				$this->setTemplateFile('clean_document');
				return;
			}
			
			//필터값 없을시 리턴
			if(!$args->target_module_srls || (!$args->target_module_srls && !$args->regdate && !$args->read_count && !$args->vote_count && !$args->blame_count && !$args->comment_count)) return $this->setTemplateFile('clean_document');
			
			//비활성게시물필터1 (등록일,조회수,추천수,댓글수,신고수)
			$args->module_srl = $args->target_module_srls;
			if($args->regdate) $args->regdate = date('Ymd',strtotime(sprintf('-%s days', $args->regdate)));
			$args->readed_count = $args->readed_count;
			$args->voted_count = $args->voted_count;
			$args->blamed_count = $args->blamed_count;
			$args->comment_count = $args->comment_count;
			$filter_result = executeQueryArray('sitemanagement.getCleanDocumentFilter1',$args)->data;
			
			//추출된 게시물의 document_srl 통합
			$document_srls = array();
			foreach($filter_result as $key => $val){
				array_push($document_srls, $val->document_srl);
			}
			$obj->document_srls = $document_srls;	//필터2,3에서 사용
			
			//비활성게시물필터2 (미열람기간)
			if($args->not_read_date) $args->not_read_date = date('YmdHis',strtotime(sprintf('-%s days', $args->not_read_date)));
			$filter_result2 = executeQueryArray('sitemanagement.getCleanDocumentFilter2',$obj)->data;
			
			//비활성게시물필터3 (첨부파일용량)
			if(in_array('attached_file', $module_config->clean_view_list)){
				$filter_result3 = executeQueryArray('sitemanagement.getCleanDocumentFilter3',$obj)->data;
			}
			
			//결과취합
			foreach($filter_result as $key => $val){
				foreach($filter_result2 as $key2 => $val2){	//필터2취합
					if($val->document_srl == $val2->document_srl){
						$val->not_read_date = $val2->regdate;
					}
				}
				if(!$val->not_read_date) $val->not_read_date = $val->regdate; //한번도 조회되지않은경우 등록일을 조회일로 가정함
				if($args->not_read_date && $val->not_read_date >= $args->not_read_date)  unset($filter_result[$key]);
				if($filter_result3){	//필터3취합
					foreach($filter_result3 as $key3 => $val3){
						if($val->document_srl == $val3->upload_target_srl){
							$val->file_count = $val3->file_count;
							$val->file_size = $val3->file_size;
							$val->download_count = $val3->download_count;
						}
					}
				}
			}
			
			//필터결과 세팅
			Context::set('result', $filter_result);
			Context::set('document_list_count', count($filter_result));
			
			//템플릿 파일 지정
            $this->setTemplateFile('clean_document');
		}
		
		//로그인 메세지 기능
        function dispSitemanagementAdminLoginMessage() {
			//회원그룹 리스트 구함  
			$oMemberModel = &getModel('member');			 
			$group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
			
			//템플릿 파일 지정
            $this->setTemplateFile('login_message');
		}
		
		//게시물 조회 모니터링
		function dispSitemanagementAdminCheckDocRead(){
			//mid 리스트 구함
			$oModuleModel = &getModel('module');
			$columnList = array('module_srl', 'mid', 'browser_title', 'module');
			$mid_list = $oModuleModel->getMidList(null, $columnList);
			Context::set('mid_list', $mid_list);
			
			//회원그룹 리스트 구함  
			$oMemberModel = &getModel('member');			 
			$group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
			
			//템플릿 파일 지정
            $this->setTemplateFile('check_doc_read');
		}
	}
?>