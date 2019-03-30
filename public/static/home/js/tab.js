$(document).ready(function () {
    //页面载入
    $(".tab-content").hide();
    $("ul.el-tabs__nav li:first").addClass("is-active").show();
    $(".tab-content:first").show();
    //单击事件
    $("ul.el-tabs__nav li").click(function () {
        $("ul.el-tabs__nav li").removeClass("is-active");
        $(this).addClass("is-active");
        $(".tab-content").hide();
        var activeTab = $(this).find("a").attr("name");
        $(activeTab).fadeIn();
        return false;
    });
});