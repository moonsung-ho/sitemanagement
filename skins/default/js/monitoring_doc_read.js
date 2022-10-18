jQuery(function($){

	//넘어온 변수내용 확인
	//var docread_check_time = '{$module_config->docread_check_time}';	 //확인주기
	//var new_sign_time = '{$module_config->new_sign_time}';	 		 //new표시시간
	//var docread_alarm_type = '{$module_config->alarm_type}';					//알림음종류
	//var docread_alarm_custom_link = '{$module_config->alarm_custom_link}'; 	//알림음파일주소 (직접지정시)
	
	//인터벌네이밍
	var checkDocreadTimer;
	
	//실시간 모니터링 기능
	$('#realtime_use').change(function(){
		if(this.checked) {
			checkDocreadTimer = setInterval(checkDocRead, docread_check_time*1000);
		}else{
			clearInterval(checkDocreadTimer);
		}
	});
	
	//게시물 조회 함수
	function checkDocRead(){
		exec_xml('sitemanagement','procSitemanagementCheckDocRead',{}, updateDocRead, ['new_doc','alarm']); //모듈이름//액션이름//보내줄값//콜백함수//콜백함수에서 받을변수(미입력시 message 기본내장)
		$('div.wfsr').css('display','none'); //통신창 숨김
	}
	
	//콜백 함수
	function updateDocRead(ret_obj){
		var new_doc = ret_obj['new_doc'];	//새로운 로그 있는지 여부
		var alarm = ret_obj['alarm'];  		//알림사용여부
		
		//알람사용시
		if(alarm){
			if(docread_alarm_type == 'custom' && docread_alarm_custom_link){
				$('body').append('<embed src="'+docread_alarm_custom_link+'" width=0 height=0></embed>');
			}else{
				$('body').append('<embed src="modules/sitemanagement/skins/default/alarm/'+docread_alarm_type+'" width=0 height=0></embed>');
			}
		}
		
		//새로운 로그 있을시
		if(new_doc){
			//로그 1개이상일때
			if(new_doc['item'].length > 1){
				for(var i in new_doc.item){
					$(new_doc.item[i]['doc_list']).prependTo('#docread_list').css('background','lightgoldenrodyellow').delay(new_sign_time*1000).queue(function(){
						$(this).css('background','#fff');
						remove_newsign(this);	//new제거
					});
				}
			}else{	
			//로그 1개일때
				if(Array.isArray(new_doc.item)){
					$(new_doc.item[0]['doc_list']).prependTo('#docread_list').css('background','lightgoldenrodyellow').delay(new_sign_time*1000).queue(function(){
						$(this).css('background','#fff');
						remove_newsign(this);	//new제거
					});	
				}else{
					$(new_doc.item['doc_list']).prependTo('#docread_list').css('background','lightgoldenrodyellow').delay(new_sign_time*1000).queue(function(){
						$(this).css('background','#fff');
						remove_newsign(this);	//new제거
					});	
				}
			}
		}		
	}
});

//new 제거함수
function remove_newsign(selector){
	jQuery(selector).find('.new_sign').remove();
}

//ajax 기능사용중 확인메세지 안나오도록 처리
jQuery.ajaxSetup({
	global: false
});