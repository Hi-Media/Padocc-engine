/**
 *
 * @projectDescription HTML table headers intelligently scroll along with the page
 * @author Rokas Šleinius - raveren@gmail.com
 * @version 1.03
 *
 * http://code.google.com/p/js-scroll-table-header/
 * 2010-03-22
 *
 */

 /**
 * Known issues:
 * - Should look strange when used with rowspan'ed tables (not tested)
 */

function ScrollTableHeader()
{
    var _mainContainer = new Array();
    var _sthTableIds = new Array();
    var _thTimer;

    var that = this;

  //public variables; change to customize
    // table must have this many rows to be processed
    this.minTableRows = 4;
    // to minimize cpu load, headers are repositioned after this many ms
    this.delayAfterScroll = 0;
  //end public variables

  // public setters
    this.addTbody = function(id) {
        _sthTableIds.push(id);
        return this;
    };

    this.addTbodies = function(/* ids */) {
        if ( arguments.length === 1 ) {
            if (arguments[0].constructor === Array) {
                _sthTableIds = _sthTableIds.concat(arguments[0]);
            } else if (arguments[0].constructor === Object){ //passed from constructor
                var argLength = arguments[0].length;
                for ( var i = 0; i < argLength; i++ ) {
                    _sthTableIds.push(arguments[0][i]);
                }
            } else {
                _sthTableIds = _sthTableIds.concat(arguments[0].split(','));
            }
        } else {
            var argLength = arguments.length;
            for ( var i = 0; i < argLength; i++ ) {
                _sthTableIds.push(arguments[i]);
            }
        }
        return this;
    };
  //end public setters; private methods follow

    function _sthInit() {
        var container,trStorage,idsLength,trs,tbodyLength,o,tr,i,tbody;
        idsLength = _sthTableIds.length;
        for ( i = 0; i < idsLength ; i++ ) {
            container = new Object();
            trStorage = [];

            tbody = document.getElementById(_sthTableIds[i]);
            tbody.sthHeaderMoved = false;

            trs = tbody.getElementsByTagName('TR');
            tbodyLength = trs.length;

            if ( tbodyLength < that.minTableRows) continue;

            for (o = 0; o < tbodyLength; o++ ) {
                tr = trs[o];
                tr.sthTop = _sthPos(tr);
                trStorage.push(tr);
            }

            container.trs = trStorage;
            container.tbody = tbody;
            container.topY = _sthPos(trs[0]) + trs[0].offsetHeight;
            container.bottomY = _sthPos(trs[trs.length-2]);

            _mainContainer.push(container);
        }
        if ( this.delayAfterScroll === 0 ) {
            _aE(window,'scroll',_sthScroll);
        } else {
            _aE(window,'scroll',_sthDelayScroll);
        }

        // for cases after a refresh when the view point is not at the very top
        _sthDelayScroll();
    }

    function _sthDelayScroll() {
        window.clearTimeout(that._sthTimer);
        that._sthTimer = window.setTimeout(_sthScroll,that.delayAfterScroll);
    }

    function _sthScroll() {
        var winTop,tbodiesLength,container,trsLength,tr,trs,o,i,tbody;

		winTop = (typeof(window.pageYOffset) == 'number')
			? window.pageYOffset
			: (document.body && document.body.scrollTop)
				? document.body.scrollTop
				: (document.documentElement && document.documentElement.scrollTop)
					? document.documentElement.scrollTop
					: 0;

        tbodiesLength = _mainContainer.length;
        for (i = 0; i < tbodiesLength; i++ ) {

            container = _mainContainer[i];

            if ( winTop > container.topY && winTop < container.bottomY ) {
                trs = container.trs;
                trsLength = trs.length;
                for (o = 0; o < trsLength; o++ ) {
                    //find the topmost visible row
                    if ( trs[o].sthTop > winTop ) {
                        tr = trs[o+1];
                        //remove the header
                        //..and place it in place of the found row and stop
                        tbody = container.tbody;
                        tbody.insertBefore(tbody.removeChild(trs[0]),tr);
                        //mark as header removed
                        tbody.sthHeaderMoved = true;
                        break;
                    }
                }
            } else if (container.tbody.sthHeaderMoved === true) {
                tbody = container.tbody;
                tbody.insertBefore(tbody.removeChild(container.trs[0]),container.trs[1]);
                tbody.sthHeaderMoved = false;
            }
        }
    }

    function _sthPos(el) {
        var top = el.offsetTop, parent;

        while(el.offsetParent !== null){
            parent = el.offsetParent;
            top += parent.offsetTop;
            el = parent;
        }
        return top;
    }
    if ( arguments.length > 0 ) {
        // console.log(Array.prototype.split.call(arguments,','))
        this.addTbodies(arguments);
    }

    //add event
    function _aE(a,b,c){
        if(a.addEventListener){a.addEventListener(b,c,null)}else{a.attachEvent("on"+b,c)}
    }

    _aE(window,'load',_sthInit);
}