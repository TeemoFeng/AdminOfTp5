mui('body').on('tap','a',function(){
    window.top.location.href=this.href;
});	
function get_code(length){
	if(length == undefined){
		length = 4;
	}
	var pow = Math.pow(10,length);	
	var code = Math.floor(Math.random() * pow + pow / 10).toString();
	return code.substr(0, length);
}