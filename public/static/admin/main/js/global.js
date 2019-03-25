$(function(){
	var $regularlyItems=$(".regularly-item-list li");
	$regularlyItems.each(function(){
		var _$this=$(this);
		_$this.children(".flip-box").eq(0).addClass("out").removeClass("in");
	    setTimeout(function() {
	    	_$this.find(".flip-box").show().eq(1).addClass("in").removeClass("out");
	    	_$this.children(".flip-box").eq(0).hide();
	    }, 225);
	});
	$regularlyItems.hover(function(){
		var _$this=$(this);
	    _$this.children(".flip-box").eq(1).addClass("out").removeClass("in");
	    setTimeout(function() {
	    	_$this.find(".flip-box").show().eq(0).addClass("in").removeClass("out");
	    	_$this.children(".flip-box").eq(1).hide();
	    }, 225);
	},function(){
		var _$this=$(this);
		_$this.children(".flip-box").eq(0).addClass("out").removeClass("in");
	    setTimeout(function() {
	    	_$this.find(".flip-box").show().eq(1).addClass("in").removeClass("out");
	    	_$this.children(".flip-box").eq(0).hide();
	    }, 225);
	});
});
