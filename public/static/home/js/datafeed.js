'use strict';

/**
 * 使用http处理数据，接入金塔的数据
 **/
var Datafeeds = {};

function getDecimal(){
    // var decimal = $('#kline-digit').val();
    var decimal = 2;
    if(window.sessionStorage.getItem("bitInfo") != null){
        decimal = JSON.parse(window.sessionStorage.getItem("bitInfo")).cnyDigit;
    }
    var str = "1";
    if(decimal>0){
        for (var i=1;i<=decimal;i++ ){
            str = str+"0";
        }
    }
    return parseInt(str);
}

/**
 * 初始化方法
 * @param datafeedURL
 * @param ws
 * @param updateFrequency
 * @constructor
 */
function compare(property) {
    return function(a, b) {
        var value1 = a[property];
        var value2 = b[property];
        return value1 - value2;
    }
}

Datafeeds.UDFCompatibleDatafeed = function(httpURL, symbol) {
    var that = this;
    // this._datafeedURL = datafeedURL;
    // 用于临时存储回调函数
    this._dataCallBacks = {};
    // 存储当前的时间间隔,1m,5m
    this._currentSymbol = symbol;
    this._currentName = symbol;
    this._current_resolution = "15min";
};
/**
 * 构造默认配置
 *     {"supports_search":true,"supports_group_request":false,"supports_marks":true,"supports_timescale_marks":true,"supports_time":true,"exchanges":[{"value":"","name":"All Exchanges","desc":""},{"value":"NasdaqNM","name":"NasdaqNM","desc":"NasdaqNM"},{"value":"NYSE","name":"NYSE","desc":"NYSE"},{"value":"NCM","name":"NCM","desc":"NCM"},{"value":"NGM","name":"NGM","desc":"NGM"}],"symbols_types":[{"name":"All types","value":""},{"name":"Stock","value":"stock"},{"name":"Index","value":"home"}],"supported_resolutions":["D","2D","3D","W","3W","M","6M"]}

 */
function defaultConfig() {

    return {
        "supports_search": false,
        "supports_group_request": false,
        "supports_marks": false,
        "supports_timescale_marks": false,
        "supports_time": true,

        "exchanges": [{
            "value": "",
            "name": "All Exchanges",
            "desc": ""
        }, {
            "value": "NasdaqNM",
            "name": "NasdaqNM",
            "desc": "NasdaqNM"
        }, {
            "value": "NYSE",
            "name": "NYSE",
            "desc": "NYSE"
        }, {
            "value": "NCM",
            "name": "NCM",
            "desc": "NCM"
        }, {
            "value": "NGM",
            "name": "NGM",
            "desc": "NGM"
        }],
        "symbols_types": [{
            "name": "All types",
            "value": ""
        }, {
            "name": "Stock",
            "value": ""
        }, {
            "name": "Index",
            "value": ""
        }],
        // "supported_resolutions": ['1', '5', '15', '30', '60', 'D', 'W', 'M']
        "supported_resolutions": ['1', '5', '15','20', '30', '60', '1440', '10080', '43200']
    }
};

/**
 * onready 方法
 * @param callback
 */
Datafeeds.UDFCompatibleDatafeed.prototype.onReady = function(callback) {
    setTimeout(function() {
        callback(defaultConfig());
    }, 0);
};

/**
 * 搜索交易对
 * @param searchString
 * @param exchange
 * @param type
 * @param onResultReadyCallback
 */
Datafeeds.UDFCompatibleDatafeed.prototype.searchSymbols = function(searchString, exchange, type, onResultReadyCallback) {

};

/**
 * {"name":"AAPL","exchange-traded":"NasdaqNM","exchange-listed":"NasdaqNM","timezone":"America/New_York","minmov":1,"minmov2":0,"pointvalue":1,"session":"0930-1630","has_intraday":false,"has_no_volume":false,"description":"Apple Inc.","type":"stock","supported_resolutions":["D","2D","3D","W","3W","M","6M"],"pricescale":100,"ticker":"AAPL","base_name":["AAPL"],"legs":["AAPL"],"exchange":"NasdaqNM","full_name":"NasdaqNM:AAPL","pro_name":"NasdaqNM:AAPL","data_status":"streaming"}
 */
function defaultSymbolInfo(datafeed) {
    return {
        "name": datafeed._currentName,
        "exchange-traded": "NasdaqNM",
        "exchange-listed": "NasdaqNM",
        "timezone": "Asia/Shanghai",
        "minmov": 1,
        "minmov2": 0,
        "pointvalue": 1,
        "session": "24x7",
        "has_intraday": true,
        "has_no_volume": false,
        "type": "bitcoin",
        "supported_resolutions": ['1', '5', '15','20', '30', '60' , '1440', '10080', '43200'],
        "has_weekly_and_monthly":true,
        "has_daily":true,
        "pricescale": getDecimal(),
        "ticker": datafeed._currentName,
        "exchange": "HOTCOIN",
        "data_status": "streaming"
    };
}

/**
 *
 * 当需要根据交易对的名字获得交易对的详细信息的时候调用
 * @param symbolName
 * @param onSymbolResolvedCallback
 *
 * @param onResolveErrorCallback
 */
Datafeeds.UDFCompatibleDatafeed.prototype.resolveSymbol = function(symbolName, onSymbolResolvedCallback, onResolveErrorCallback) {
    var that = this;

    setTimeout(function() {
        onSymbolResolvedCallback(defaultSymbolInfo(that));
    }, 0);

};

/**
 *
 * @param symbolInfo
 * @param resolution
 * @param from
 * @param to
 * @param onHistoryCallback
 * @param onErrorCallback
 * @param firstDataRequest 是否是第一次加载数据,第一次加载数据的时候，可以忽略to
 *
 * {time, close, open, high, low, volume}
 *
 */

window.getBarsArr = [];
Datafeeds.UDFCompatibleDatafeed.prototype.getBars = function(symbolInfo, resolution, fromP, toP, onHistoryCallback, onErrorCallback, firstDataRequest) {
    //是否第一次请求，防止出项数据不足导致的频繁请求的问题
    if(!firstDataRequest) {
        setTimeout(function(){
            firstReq()
        },3000)
        return;
    }else{
        firstReq();
    }
    function firstReq(){
        var symbol = 8;
        if(window.sessionStorage.getItem('currentObject') != null){
            symbol = JSON.parse(window.sessionStorage.getItem('currentObject')).tradeId;
        }
        var url='https://hktestweb.hotcoin.top/kline/fullperiod.html';
        if(window.location.host == "hotcoin.top" || window.location.host == "www.hotcoin.top" ){
            url = "https://hkweb.hotcoin.top/kline/fullperiod.html"
        }
        $.ajax({
            type: "post",
            url:  url,
            async: true,
            dataType: 'json',
            data: {
                symbol: symbol,
                step: resolution * 60,
            },
            success: function(data) {
                var array = [];
                for(var i =0; i<data.length;i++){
                    var barValue = {};
                    barValue.time = data[i][0];
                    barValue.open = data[i][1];
                    barValue.high = data[i][2];
                    barValue.low = data[i][3];
                    barValue.close = data[i][4];
                    barValue.volume = Number(data[i][5]);
                    array.push(barValue);
                    array.sort(compare('time'))
                }
                // 通过判断请求数据是否与上次请求的数据相同，决定是否重新渲染数据
                // （同时解决当页面的数据不足时，TradingView会不停的执行getBars函数请求其他不足的数据，而造成的死循环）
                // console.log(JSON.stringify(window.getBarsArr) != JSON.stringify(array))
                // if(JSON.stringify(window.getBarsArr) != JSON.stringify(array)){
                //   window.getBarsArr = array
                //   onHistoryCallback(array);
                // }
                onHistoryCallback(array);
            },
            error: function(e) {
                // console.log("kline error");
            }
        });
    }

};

/**
 * Charting Library calls this function when it wants to receive real-time updates for a symbol.
 * The Library assumes that you will call onRealtimeCallback every time you want to update the most recent bar or to add a new one.
 * @param symbolInfo
 * @param resolution
 * @param onRealtimeCallback
 * @param listenerGUID
 * @param onResetCacheNeededCallback
 */
var time1 = "";

Datafeeds.UDFCompatibleDatafeed.prototype.subscribeBars = function(symbolInfo, resolution, onRealtimeCallback, listenerGUID, onResetCacheNeededCallback) {
    clearInterval(time1);
    var symbol = 8;
    if(window.sessionStorage.getItem('currentObject') != null){
        symbol = JSON.parse(window.sessionStorage.getItem('currentObject')).tradeId;
    }

    time1 = setInterval(req,2000);
    var data = {
        symbol:symbol,
        step : resolution*60
    };
    var url='https://hktestweb.hotcoin.top/kline/lastperiod.html';
    if(window.location.host == "hotcoin.top" || window.location.host == "www.hotcoin.top" ){
        url = "https://hkweb.hotcoin.top/kline/lastperiod.html"
    }
    function req(){
        $.ajax({
            type: "get",
            url:  url,
            async: true,
            dataType: 'json',
            data: data,
            success: function(data) {
                var barValue={ };
                if(data.code=200){
                    barValue.time = data.data[0][0];
                    barValue.open = data.data[0][1];
                    barValue.high = data.data[0][2];
                    barValue.low = data.data[0][3];
                    barValue.close = data.data[0][4];
                    barValue.volume = Number(data.data[0][5]);
                }
                onRealtimeCallback(barValue);
            },
            error: function(e) {
                console.log("kline error");
            }
        });
    }



};

/**
 * Charting Library calls this function when it doesn't want to receive updates for this subscriber any more. subscriberUID will be the same object that Library passed to subscribeBars before.
 * @param listenerGUID
 */
Datafeeds.UDFCompatibleDatafeed.prototype.unsubscribeBars = function(listenerGUID) {

};

/**
 * Charting Library calls this function when it is going to request some historical data to give you an ability to override the amount of bars requested.
 * @param period
 * @param resolutionBack
 * @param intervalBack
 */
Datafeeds.UDFCompatibleDatafeed.prototype.calculateHistoryDepth = function(period, resolutionBack, intervalBack) {

};


/**
 * 当需要支持点击弹出提示的时候用到
 * @param symbolInfo
 * @param rangeStart
 * @param rangeEnd
 * @param onDataCallback
 * @param resolution
 */
Datafeeds.UDFCompatibleDatafeed.prototype.getMarks = function(symbolInfo, rangeStart, rangeEnd, onDataCallback, resolution) {
    console.log("getMarks");
};

/**
 * 点击事件提示的时候用到
 * @param symbolInfo
 * @param rangeStart
 * @param rangeEnd
 * @param onDataCallback
 * @param resolution
 */
Datafeeds.UDFCompatibleDatafeed.prototype.getTimescaleMarks = function(symbolInfo, rangeStart, rangeEnd, onDataCallback, resolution) {

};

/**
 * 获取服务器时间
 * @param callback
 */
Datafeeds.UDFCompatibleDatafeed.prototype.getServerTime = function(callback) {
    var timestamp = new Date().getTime();
    callback(timestamp);
};

/**
 * 报价信息
 * @param symbols
 * @param onDataCallback
 * @param onErrorCallback
 */
Datafeeds.UDFCompatibleDatafeed.prototype.getQuotes = function(symbols, onDataCallback, onErrorCallback) {
    console.log("getQuotes");
};

/**
 * 订阅报价信息
 * @param symbols
 * @param fastSymbols
 * @param onRealtimeCallback
 * @param listenerGUID
 */
Datafeeds.UDFCompatibleDatafeed.prototype.subscribeQuotes = function(symbols, fastSymbols, onRealtimeCallback, listenerGUID) {

    console.log("subscribeQuotes");
};

/**
 * 解除报价订阅
 * @param listenerGUID
 */
Datafeeds.UDFCompatibleDatafeed.prototype.unsubscribeQuotes = function(listenerGUID) {
    console.log("listenerGUID");
};
