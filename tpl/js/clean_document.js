// 선택회원삭제
function jsDeleteCleanDocument() {
    var data = xGetElementById("log_data_table");
    var document_srl = new Array();

    if(typeof(data.cart.length)=='undefined') {
        if(data.cart.checked) document_srl[document_srl.length] = data.cart.value;
    } else {
        var length = data.cart.length;
        for(var i=0; i<length; i++) {
            if(data.cart[i].checked) document_srl[document_srl.length] = data.cart[i].value;
        }
    }
	
	//로그 선택하지 않았을때 오류메세지 출력
    if(document_srl.length < 1) { alert('선택된 게시물이 없습니다'); return; }
	//삭제 취소시 리턴
    if(!confirm('선택한 게시물을 삭제합니다.\n선택된 게시물의 댓글 또한 함께 삭제됩니다.\n삭제된 게시물은 복구가 불가능하니 신중히 검토해주세요')) return;

	//act에 넘겨줄 배열생성
    var params = new Array();
    params['document_srls'] = document_srl.join('@'); //값을 하나로 합침
    exec_xml('sitemanagement','procSitemanagementAdminCleanDocumentDelete', params, completeDeletelog); //모듈이름//액션이름//보내줄값//콜백함수//콜백함수에서 받을변수(미입력시 message 기본내장)
}

/* 일괄 삭제 후 */
function completeDeletelog(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}