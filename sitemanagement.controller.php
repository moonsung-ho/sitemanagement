<?php
    
	/**********************************
	**		  컨트롤러 클래스		 **
	***********************************/

    class sitemanagementController extends sitemanagement {

        //초기화
        function init() {
			// 사용자 템플릿 파일의 경로 설정 (skins)
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'default';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            $this->setTemplatePath($template_path);
		}
		
		//트리거 (활동제한기능)
		function triggerBeforeModuleObjectProc(&$obj){
			//모듈번호 없을시 리턴
			if(!$obj->module_srl) return;
			
			//모듈설정 구함
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');	//모듈설정
			$module_part_config = $oModuleModel->getModulePartConfig('sitemanagement',$obj->module_srl); //모듈부분설정
			
			//기능 미사용시 리턴
			if($module_config->limit_access_use != 'yes') return;
			
			//회원정보 구함 //관리자 리턴은 필요에 따라 사용할것
			$logged_info = Context::get('logged_info');
			
			//관리자 제한없음 사용시 리턴
			if($logged_info->is_admin == 'Y' && $module_config->limit_access_admin_pass == 'yes') return;
			
			//회원레벨 구함 (설정값 존재시)
			if($module_part_config['limit_access_module']['level'] || $module_part_config['limit_access_document']['level']){
				if($logged_info){
					$oPointModel = &getModel('point');
					$point_config = $oModuleModel->getModuleConfig('point');
					$member_point = $oPointModel->getPoint($logged_info->member_srl);
					$member_level = $oPointModel->getLevel($member_point, $point_config->level_step);
				}
			}
			
			//작성한 게시물 수 가져옴 (설정값 존재시)
			if($module_part_config['limit_access_module']['doc'] || $module_part_config['limit_access_document']['doc']){
				if($logged_info){
					$args = new stdClass();
					$args->regdate_more = $module_part_config['limit_date'] ? date('Ymd',strtotime(sprintf('-%s days', $module_part_config['limit_date']))) : '';
					$args->member_srl = abs($logged_info->member_srl);
					$member_doc_count = executeQuery('sitemanagement.getDocumentCountByMemberSrl', $args)->data->count;
				}else{ 
					$member_doc_count = 0;
				}
			}
			
			//작성한 덧글 수 가져옴 (설정값 존재시)
			if($module_part_config['limit_access_module']['com'] || $module_part_config['limit_access_document']['com']){
				if($logged_info){
					$args = new stdClass();
					$args->regdate_more = $module_part_config['limit_date'] ? date('Ymd',strtotime(sprintf('-%s days', $module_part_config['limit_date']))) : '';
					$args->member_srl = abs($logged_info->member_srl);
					$member_com_count = executeQuery('sitemanagement.getCommentCountByMemberSrl', $args)->data->count;
				}else{
					$member_com_count = 0;
				}
			}
						
			/***************************
			* 모듈 접근 제한
			****************************/
			//레벨확인
			if($module_part_config['limit_access_module']['level'] && $module_part_config['limit_access_module']['level'] > $member_level){
				$message = '레벨 부족으로 인해 접근이 제한되었습니다.\n'.$module_part_config['limit_access_module']['level'].' 레벨 이후 접근이 가능합니다.';
				if(!Context::get('logged_info')) $message = '로그인이 필요합니다';
				$this->alertMsg($message);
			}
			//게시글수 확인
			if($module_part_config['limit_access_module']['doc'] && $module_part_config['limit_access_module']['doc'] > $member_doc_count){
				$msg_date = $module_part_config['limit_date'] ? $module_part_config['limit_date'].'일간 ' : '';
				$message = '활동 부족으로 인해 접근이 제한되었습니다.\n'.$msg_date.'게시글 '.$module_part_config['limit_access_module']['doc'].'개 이상 작성 후 접근이 가능합니다.\n현재 작성한 게시글은 '.$member_doc_count.'개 입니다.';
				if(!Context::get('logged_info')) $message = '로그인이 필요합니다';
				$this->alertMsg($message);
			}
			//댓글수 확인
			if($module_part_config['limit_access_module']['com'] && $module_part_config['limit_access_module']['com'] > $member_com_count){
				$msg_date = $module_part_config['limit_date'] ? $module_part_config['limit_date'].'일간 ' : '';
				$message = '활동 부족으로 인해 접근이 제한되었습니다.\n'.$msg_date.'댓글 '.$module_part_config['limit_access_module']['com'].'개 이상 작성 후 접근이 가능합니다.\n현재 작성한 댓글은 '.$member_com_count.'개 입니다.';
				if(!Context::get('logged_info')) $message = '로그인이 필요합니다';
				$this->alertMsg($message);
			}
		
			
			/***************************
			* 게시글 열람 제한
			****************************/
			$document_srl = Context::get('document_srl');
			if($obj->act == 'dispBoardContent' && $document_srl){
				//레벨확인
				if($module_part_config['limit_access_document']['level'] && $module_part_config['limit_access_document']['level'] > $member_level){
					$message = '레벨 부족으로 인해 접근이 제한되었습니다.\n'.$module_part_config['limit_access_document']['level'].' 레벨 이후 접근이 가능합니다.';
					if(!Context::get('logged_info')) $message = '로그인이 필요합니다';
					$this->alertMsg($message);
				}
				//게시글수 확인
				if($module_part_config['limit_access_document']['doc'] && $module_part_config['limit_access_document']['doc'] > $member_doc_count){
					$msg_date = $module_part_config['limit_date'] ? $module_part_config['limit_date'].'일간 ' : '';
					$message = '활동 부족으로 인해 접근이 제한되었습니다.\n'.$msg_date.'게시글 '.$module_part_config['limit_access_document']['doc'].'개 이상 작성 후 접근이 가능합니다.\n현재 작성한 게시글은 '.$member_doc_count.'개 입니다.';
					if(!Context::get('logged_info')) $message = '로그인이 필요합니다';
					$this->alertMsg($message);
				}
				//댓글수 확인
				if($module_part_config['limit_access_document']['com'] && $module_part_config['limit_access_document']['com'] > $member_com_count){
					$msg_date = $module_part_config['limit_date'] ? $module_part_config['limit_date'].'일간 ' : '';
					$message = '활동 부족으로 인해 접근이 제한되었습니다.\n'.$msg_date.'댓글 '.$module_part_config['limit_access_document']['com'].'개 이상 작성 후 접근이 가능합니다.\n현재 작성한 댓글은 '.$member_com_count.'개 입니다.';
					if(!Context::get('logged_info')) $message = '로그인이 필요합니다';
					$this->alertMsg($message);
				}
			}
		}
		
		//팝업 안내메세지
		function alertMsg($message){
			header("Content-Type: text/html; charset=UTF-8"); //헤더설정 직접 해주거나(한글인코딩) 아래주석 제거하거나 선택적 사용
			//htmlHeader();
			alertScript($message);
			echo '<script type="text/javascript">history.back()</script>';
			//htmlFooter();
			Context::close();
			exit;
		}
				
		//로그인 메세지 기능1-1
		function triggerAfterLogin(&$obj){
			//마지막 로그인 날짜 쿠키생성, 30초후 제거
			setcookie("last_login_time", $obj->last_login, time()+30, "/");	
		}
		
		//로그인 메세지 기능1-2
		function triggerDisplay(){
			//마지막 로그인 쿠키값 없을시 리턴
			if(!$_COOKIE['last_login_time']) return;
			
			//변수에 담고 쿠키제거
			$last_login = $_COOKIE['last_login_time'];
			setcookie("last_login_time", "", time()-600, "/");	//쿠키제거
			
			//로그인정보 가져옴 없을시 리턴
			$logged_info = Context::get('logged_info');
			if(!$logged_info) return;
			
			//모듈설정 구함
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');	//모듈설정
			
			//메세지 기능 미사용시 리턴
			if($module_config->login_message_use != 'yes') return; 
			
			//적용 회원그룹 아닐경우 리턴
			if(!array_intersect($logged_info->group_list,$module_config->login_message_target_group)) return;
			
			//1일간 출력횟수 초과시 리턴
			if($module_config->login_message_use_type == 'count'){
				//쿠키 만료기한 타임스탬프로 변환
				$today = date('Y-m-d').'23:59:59';
				$today = strtotime($today);
				//출력횟수 갱신
				if( $_COOKIE['login_use_count'] ) {
					$login_use_count = $_COOKIE['login_use_count'];
					setcookie("login_use_count", $login_use_count+1, $today, "/");	
				}else{
					setcookie("login_use_count", 1, $today, "/");	
				}
				if($module_config->login_message_use_count <= $_COOKIE['login_use_count']) return;
			}
			
			//접속시간계산
			$date = (strtotime(date('YmdHis')) - strtotime($last_login) );
			if($date < 60){
				$time = $date.' 초';
			}elseif($date < 3600){
				$time = round($date/60).' 분';
			}elseif($date < 86400){
				$time = round($date/60/60).' 시간';
			}else{
				$time = round($date/60/60/24).' 일';
			}
			
			//접속시간 메세지 세팅
			if($module_config->time_msg_use == 'yes'){
				$time_msg = str_replace(array("\r\n","\r","\n"),'',$module_config->time_msg); ; 	//엔터제거
				$time_msg = str_replace(array('[time]','[nick_name]','[enter]'),array($time,$logged_info->nick_name,'\n'),$time_msg);
			}
			
			//활동내역계산
			$args = new stdClass();
			$args->regdate_more = date('YmdHis', strtotime('-'.$module_config->info_msg_date.' days'));
			$args->member_srl = $logged_info->member_srl;
			$doc_count = executeQuery('sitemanagement.getDocumentCountByMemberSrl',$args)->data->count;
			$com_count = executeQuery('sitemanagement.getCommentCountByMemberSrl',$args)->data->count;
			
			//활동내역 메세지 세팅
			if($module_config->info_msg_use == 'yes'){
				$info_msg = str_replace(array("\r\n","\r","\n"),'',$module_config->info_msg); ; 	//엔터제거
				$info_msg = str_replace(array('[date]','[doc]','[com]','[nick_name]','[enter]'),array($module_config->info_msg_date,$doc_count,$com_count,$logged_info->nick_name,'\n'),$info_msg);
				//활동내역 미달 메세지 사용시
				if($module_config->info_msg2_use == 'yes'){
					//조건 충족시
					if($doc_count <= $module_config->info_msg_doc_count && $com_count <= $module_config->info_msg_com_count){
						$info_msg = str_replace(array("\r\n","\r","\n"),'',$module_config->info_msg2); ; 	//엔터제거
						$info_msg = str_replace(array('[date]','[doc]','[com]','[nick_name]','[enter]'),array($module_config->info_msg_date,$doc_count,$com_count,$logged_info->nick_name,'\n'),$info_msg);
					}
				}
			}
			
			//생일일때
			$today = date('Ymd');
			if($logged_info->birthday == $today){
				//포인트지급
				if($module_config->birthday_point && !$_COOKIE['birthday_check']){
					//중복지급 방지위해 쿠키생성
					setcookie("birthday_check", 'yes', time()+60*60*24, "/");
					//포인트히스토리 모듈에 포인트 획득내용 기록 (제거해도됨)
					$PHC_member_srl = $logged_info->member_srl;
					$PHC_content = '생일 축하 포인트 지급'; 
					eval('$__PHC'.$PHC_member_srl.'__[] = array($PHC_content,$PHC_point,$PHC_type);');
					eval('Context::set(\'__PHC\'.$PHC_member_srl.\'__\',$__PHC'.$PHC_member_srl.'__);');					
					//포인트지급
					$oPointController = &getController('point');
					$oPointController->setPoint( $logged_info->member_srl, $module_config->birthday_point, 'add' );
				}
				//생일 메세지 세팅 (포인트 지급받은경우 작동되지 않음)
				if($module_config->birthday_msg_use == 'yes' && !$_COOKIE['birthday_check']){
					$birthday_msg = str_replace(array("\r\n","\r","\n"),'',$module_config->birthday_msg); ; 	//엔터제거
					$birthday_msg = str_replace(array('[point]','[nick_name]','[enter]'),array($module_config->birthday_point,$logged_info->nick_name,'\n'),$birthday_msg);
				}
			}
			
			
			//최종 메세지 조합
			$msg_set = str_replace(array("\r\n","\r","\n"),'',$module_config->msg_set); ; 	//엔터제거
			$message = str_replace(array('[time_msg]','[info_msg]','[birthday_msg]','[enter]'),array($time_msg,$info_msg,$birthday_msg,'\n'),$msg_set);
			
			//팝업으로 메세지 보냄
			Context::addHtmlFooter('<script type="text/javascript">alert("'.$message.'")</script>');
		}
		
		//트리거 (비활성 게시물정리 및 게시글 조회 모니터링 기능)
		function triggerUpdateReadedCountBefore(&$obj){
			
			//문서 조회기록 있는지 확인
			$args = new stdClass();
			$args->document_srl = $obj->document_srl;
			$doc_read_history = executeQuery('sitemanagement.docRead_get',$args)->data['1'];
			
			//회원정보 구함
			$logged_info = Context::Get('logged_info');
			if($logged_info) $args->member_srl = $logged_info->member_srl;
			
			//db처리
			if($doc_read_history){
				executeQuery('sitemanagement.docRead_update',$args);	//문서 조회기록 있으면 업데이트
			}else{
				//추가 변수정리
				$args->module_srl = $obj->variables['module_srl'];
				$args->title = $obj->variables['title'];
				executeQuery('sitemanagement.docRead_insert',$args);	//문서 조회기록 없으면 db입력
			}
		}
		
		//게시물 조회 모니터링 ajax
		function procSitemanagementCheckDocRead(){
			
			//모듈설정 구함
			$oModuleModel = &getModel('module');
			$module_config = $oModuleModel->getModuleConfig('sitemanagement');
			
			//로그인정보구함
			$logged_info = Context::get('logged_info');
			
			//로그추출
			$args = new stdClass();
			$args->exclude_module_srls = $module_config->exclude_module_srls;
			$args->regdate_more = date('YmdHis', time()-$module_config->docread_check_time);
			$new_doc = executeQueryArray('sitemanagement.docRead_get',$args)->data;
			
			//새로운 로그 있을시
			if(count($new_doc)){
				$oMemberModel = getModel('member');
				$oModuleModel = getModel('module');
				//리스트 가공
				foreach($new_doc as $key => $val){
					$member_info = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl);
					//회원손님 구분
					if(in_array('type', $module_config->docread_view)){
						if($logged_info->is_admin == 'Y') $info = 'title="'.$member_info->nick_name.'"';
						$user_type = $member_info ? '<a class="read_info" '.$info.'><span><img src="modules/sitemanagement/skins/default/img/user_login.png" alt="user_img" />회원</span></a>' : '<a class="read_info"><span><img src="modules/sitemanagement/skins/default/img/user.png" alt="visitor_img" />손님</span></a>';
					}
					//제목
					if(in_array('subject', $module_config->docread_view)){
						$title = cut_str($val->title,$module_config->docread_cut_str,'...');
						$url = getUrl().$val->document_srl;
						$subject = '<a class="doc_title" href="'.$url.'" target="_blank"><span>'.$title.'</span></a>';
					}
					//모듈타이틀
					if(in_array('mid', $module_config->docread_view)){
						$oModuleInfo = $oModuleModel->getModuleInfoByModuleSrl($val->module_srl); 
						$browser_title = $oModuleInfo->browser_title;
						$module_title = '<a class="read_module"><span>from '.$browser_title.'</span></a>';
					}
					//조회시간
					if(in_array('regdate', $module_config->docread_view)){
						$min_date = (strtotime(date('YmdHis')) - strtotime($val->regdate) );
						if($min_date < 60){
							$doc_date = '<a class="read_time"><span>'.$min_date.'초전</span><img src="modules/sitemanagement/skins/default/img/time.png" alt="img" /></a>';
						}elseif($min_date > 60 && $min_date < 3600){	
							$doc_date = '<a class="read_time"><span>'.round($min_date/60).'분전</span><img src="modules/sitemanagement/skins/default/img/time.png" alt="img" /></a>';
						}elseif($min_date > 3600 && $min_date < 86400){
							$doc_date = '<a class="read_time"><span>'.round($min_date/60/60).'시간전</span><img src="modules/sitemanagement/skins/default/img/time.png" alt="img" /></a>';
						}else{
							$doc_date = '<a class="read_time"><span style="color:black">'.date('Y년 m월 d일', strtotime($val->regdate)).'</span><img src="modules/sitemanagement/skins/default/img/time.png" alt="img" /></a>';
						}
					}
					//ip주소
					if($logged_info->is_admin == 'Y' && in_array('ipaddress', $module_config->docread_view)) $ipaddress = '<a class="read_ip"><span>'.$val->ipaddress.'</span></a>';
					//new표시
					$new_sign = '<span class="new_sign">new</span>';
					//리스트 정리
					$val->doc_list = '<li>'.$user_type.$subject.$module_title.$doc_date.$ipaddress.$new_sign.'</li>';
				}
							
				//변수세팅
				$this->add('new_doc',$new_doc);
				
				//알람기능
				if($module_config->alarm_use == 'yes') $this->add('alarm',true);
			}
			
			
		}
		
	}
?>