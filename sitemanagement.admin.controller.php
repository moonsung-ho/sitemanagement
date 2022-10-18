<?php
    
	/*************************************
	**		관리자 컨트롤러 클래스	 	**
	**************************************/

    class sitemanagementAdminController extends sitemanagement {

        //초기화
        function init() {           
        }

		//관리자 모듈설정저장
		function procSitemanagementAdminModuleInfo(){
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			$args->module = 'sitemanagement';
			//모듈등록 유무에 따라 insert/update
			$oModuleController = &getController('module');
			if(!$args->module_srl){
				$output = $oModuleController->insertModule($args); //모듈insert
				$this->setMessage('success_registed');
			}else{ 
				$output = $oModuleController->updateModule($args); //모듈update
				$this->setMessage('success_updated');
			}
            if(!$output->toBool()) return $output;
			//모듈시작 화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispSitemanagementAdminModuleInfo')); 
		}		
		
		//활동제한설정
		function procSitemanagementAdminLimitAccessConfig(){
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			
			//기존모듈설정 가져옴
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//모듈별로 설정묶음
			$configList = array('limit_access_module', 'limit_access_document', 'limit_date');
			foreach($configList as $config){	
				foreach($args->{$config} as $key => $val){
					$module_part_config[$key][$config] = $val;
				}
			}
			//모듈별 설정입력
			$oModuleController = &getController('module');
			if(count($module_part_config)){
				foreach($module_part_config as $module_srl => $config){
					$oModuleController->insertModulePartConfig('sitemanagement',$module_srl,$config);
				}
			}
			
			//모듈설정저장
			$module_config->module = 'sitemanagement';	//모듈명
			$module_config->limit_access_use = $args->limit_access_use ? $args->limit_access_use : 'no'; 						//활동제한기능 사용여부
			$module_config->limit_access_admin_pass = $args->limit_access_admin_pass ? $args->limit_access_admin_pass : 'no'; 	//관리자제한없음 사용여부
            $oModuleController->insertModuleConfig('sitemanagement', $module_config);
			
			// 성공메세지
            $this->setMessage('success_updated');
			
			//모듈시작 화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispSitemanagementAdminLimitAccessConfig')); 
		}		
		
		//유령회원정리 설정
		function procSitemanagementAdminGhostMemberConfig(){
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			
			//기존모듈설정 가져옴
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//모듈설정저장
			$oModuleController = &getController('module');
			$module_config->module = 'sitemanagement';	//모듈명
			$module_config->history_delete = $args->history_delete ? $args->history_delete : 'no'; 		//흔적제거기능 사용여부
			//표시항목
			$module_config->ghost_view_list = $args->ghost_view_list ? $args->ghost_view_list : array('member_srl','email','user_id','nick_name','regdate','last_login','group_list','login_period','doc_count','com_count');
			$oModuleController->insertModuleConfig('sitemanagement', $module_config);
			
			//모듈시작 화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispSitemanagementAdminGhostMember')); 
		}
		
		//유령회원삭제
		function procSitemanagementAdminGhostMemberDelete(){
			$member_srls = Context::get('member_srls');
			if(!$member_srls) return new Object(-1,'선택 대상이 없습니다');
			
			//선택된 회원분리
			$member_srl_list = explode("@",$member_srls);
			
			//기존모듈설정 가져옴
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//회원삭제는 member모듈 함수사용
			$oMemberController = &getController('member');
			//흔적제거기능은 document, comment모듈 함수 사용
			$oDocumentController = &getController('document');
			$oCommentController = &getController('comment');
			$oDocumentModel = &getModel('document');
			$oCommentModel = &getModel('comment');
			
			//회원삭제
			foreach($member_srl_list as $key => $member_srl) {
				if($member_srl){
					//흔적제거 기능사용시 동작
					if($module_config->history_delete == 'yes'){
						//게시글삭제
						$document_list = $oDocumentModel->getDocumentListByMemberSrl($member_srl);
						if($document_list){
							foreach($document_list as $key => $val){
								if($val->document_srl) $oDocumentController->deleteDocument($val->document_srl);
							}
						}
						//댓글삭제
						$comment_list = $oCommentModel->getCommentListByMemberSrl($member_srl);
						if($comment_list){
							foreach($comment_list as $key => $val){
								if($val->comment_srl) $oCommentController->deleteComment($val->comment_srl);
							}
						}
					}
					//회원삭제
					$oMemberController->deleteMember($member_srl);
				}
			}
			
			//완료메세지
			$this->setMessage('success_deleted');
		}
		
		//비활성 게시물정리 설정
		function procSitemanagementAdminCleanDocumentConfig(){
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			
			//기존모듈설정 가져옴
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//모듈설정저장
			$oModuleController = &getController('module');
			$module_config->module = 'sitemanagement';	//모듈명
			//표시항목
			$module_config->clean_view_list = $args->clean_view_list ? $args->clean_view_list : array('document_srl','mid','subject','nick_name','regdate','read_count','vote_count','comment_count','not_read_date');
			$oModuleController->insertModuleConfig('sitemanagement', $module_config);
			
			//모듈시작 화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispSitemanagementAdminCleanDocument')); 
		}
		
		//비활성 게시물 삭제
		function procSitemanagementAdminCleanDocumentDelete(){
			$document_srls = Context::get('document_srls');
			if(!$document_srls) return new Object(-1,'선택 대상이 없습니다');
			
			//선택된 게시물분리
			$document_srl_list = explode("@",$document_srls);
			
			//게시물 삭제 기능은 document 모듈 함수 사용
			$oDocumentController = &getController('document');
			
			//게시물 삭제
			foreach($document_srl_list as $key => $document_srl) {
				if($document_srl) $oDocumentController->deleteDocument($document_srl);
			}
			
			//완료메세지
			$this->setMessage('success_deleted');
		}
		
		//로그인 메세지기능 설정
		function procSitemanagementAdminLoginMessageConfig(){
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			
			//기존모듈설정 가져옴
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//설정세팅
			$module_config->module = 'sitemanagement';	//모듈명
			$module_config->login_message_use = $args->login_message_use;					//로그인메세지 기능사용
			$module_config->login_message_target_group = $args->login_message_target_group;	//적용 회원그룹
			$module_config->login_message_use_type = $args->login_message_use_type;											//로그인 메세지 출력형태
			$module_config->login_message_use_count = $args->login_message_use_count ? $args->login_message_use_count : 1;	//1일간 출력 횟수
			$module_config->time_msg_use = $args->time_msg_use;								//접속시간 메세지
			$module_config->time_msg = $args->time_msg ? $args->time_msg : '안녕하세요! [nick_name]님![enter][time]만에 다시 방문하셨군요!' ;	//접속시간 메세지
			$module_config->info_msg_use = $args->info_msg_use;									//활동내역 메세지
			$module_config->info_msg_date = $args->info_msg_date ? $args->info_msg_date: 30 ;	//활동내역 산출기간
			$module_config->info_msg = $args->info_msg ? $args->info_msg : '현재 [date]일간 활동내역은 아래와 같습니다.[enter]게시글 작성 : [doc]개[enter]댓글 작성 : [com]개[enter][enter]오늘 하루도 잘 부탁드려요!' ;	//활동내역 메세지
			$module_config->info_msg2_use = $args->info_msg2_use;											//활동내역 미달 메세지
			$module_config->info_msg_doc_count = $args->info_msg_doc_count ? $args->info_msg_doc_count : 0;	//게시글 n개이하
			$module_config->info_msg_com_count = $args->info_msg_com_count ? $args->info_msg_com_count : 0;	//댓글 n개이하
			$module_config->info_msg2 = $args->info_msg2 ? $args->info_msg2 : '현재 [date]일간 활동내역은 아래와 같습니다.[enter]게시글 작성 : [doc]개[enter]댓글 작성 : [com]개[enter][enter]조금 더 분발해주세요!' ;	//활동내역 미달 메세지
			$module_config->birthday_msg_use = $args->birthday_msg_use;						//생일축하 메세지
			$module_config->birthday_msg = $args->birthday_msg ? $args->birthday_msg : '[enter]생일축하합니다![enter]생일기념으로 [point] 포인트를 드려요![enter]' ;	//생일축하 메세지
			$module_config->birthday_point = $args->birthday_point;							//생일축하 포인트 
			$module_config->msg_set = $args->msg_set ? $args->msg_set : '[time_msg][enter][enter][info_msg][enter][birthday_msg]' ;	//메세지조합
			
			//모듈설정저장
			$oModuleController = &getController('module');
			$oModuleController->insertModuleConfig('sitemanagement', $module_config);
			
			//모듈시작 화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispSitemanagementAdminLoginMessage')); 
		}
		
		//게시물 조회 모니터링 설정
		function procSitemanagementAdminCheckDocReadConfig(){
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			
			//기존모듈설정 가져옴
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//설정세팅
			$module_config->module = 'sitemanagement';	//모듈명
			$module_config->exclude_module_srls = $args->exclude_module_srls;		//게시물조회 제외모듈
			$module_config->docread_access_target = $args->docread_access_target;	//모니터링 접근대상 설정
			$module_config->docread_target_user_id = $args->docread_target_user_id;	//모니터링 접근대상(회원아이디)				
			$module_config->docread_target_group = $args->docread_target_group;		//모니터링 접근대상(회원그룹)
			$module_config->docread_check_time = $args->docread_check_time ? $args->docread_check_time : 3;			//확인주기
			$module_config->docread_view = $args->docread_view;			//스킨-표시항목
			$module_config->docread_cut_str = $args->docread_cut_str;	//스킨-제목길이
			$module_config->new_sign_time = $args->new_sign_time ? $args->new_sign_time : 10;	//스킨-new표시시간
			$module_config->alarm_use = $args->alarm_use ? $args->alarm_use : 'no';				//알림음 사용여부
			$module_config->alarm_type = $args->alarm_type ? $args->alarm_type : 'alarm1.mp3';	//알림음 종류
			$module_config->alarm_custom_link = $args->alarm_custom_link;						//알림음(직접지정) 주소
			
			//모듈설정저장
			$oModuleController = &getController('module');
			$oModuleController->insertModuleConfig('sitemanagement', $module_config);
			
			//모듈시작 화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispSitemanagementAdminCheckDocRead')); 
		}
		
	}
?>