var canvasId           = 'c';
var wrapCanvasId       = 'wrap-canvas';
var canvasBgColor      = '#b3b2fe';
var defaultBgImageUrl  = '/images/img1.jpg';
var scaleSteps         = 10;
var inputForData       = 'coords';
var userRole           = 2;

var mouseDown = mouseUp = mouseMove = {};

function extend(Child, Parent) {
	var F = function() { }
	F.prototype = Parent.prototype
	Child.prototype = new F()
	Child.prototype.constructor = Child
	Child.superclass = Parent.prototype
}

function Instalation(){};

var instalation = new Instalation();

// initialization
Instalation.prototype.ini = function(){
    this.id = canvasId;
    this.canvas = new fabric.Canvas(this.id);
    this.canvas.backgroundColor = canvasBgColor;
    this.canvas.selection = false;
    this.canvas.renderAll();
    return this.canvas
}
// absolute coords
fabric.Canvas.prototype.getAbsoluteCoords = function(object) {
  return {
    left: object.x + this._offset.left,
    top: object.y + this._offset.top
  };
};

// parse color code
Instalation.prototype.pad = function(str, length){
    while (str.length < length) {
      str = '0' + str;
    }
    return str;
}
// get random color
Instalation.prototype.getRandomColor = function(){
    var getRandomInt = fabric.util.getRandomInt;
    return (
      instalation.pad(getRandomInt(0, 255).toString(16), 2) +
      instalation.pad(getRandomInt(0, 255).toString(16), 2) +
      instalation.pad(getRandomInt(0, 255).toString(16), 2)
    );
}
// parse coords from draw points
Instalation.prototype.parseCoordsPath = function(coords){
    if(coords == ''){
        points = 'M ' + mouseDown.x + ' ' + mouseDown.y + ' ';
    }else{
        points = coords + ' L ' + mouseDown.x + ' ' + mouseDown.y;
            
    }
    $('.draw-points').attr('data-coords', points);
    return points;    
}
// get coords cursor on click from canvas
Instalation.prototype.getCanvasClickCoords = function(e){
    var coords = getResultCoords(getOffsetRect(), e);
    return coords;    
}
// configurate scale steps
Instalation.prototype.configurateScaleSteps = function(){
    var stroke = '<ul>';
    
    for(i=1;i<scaleSteps;i++){
        active  = i == 1?'class="canvas-navigate-scale-active"':'';
        stroke += '<li data-num="'+i+'" '+active+'></li>';
    }
    
    stroke += '</ul>';
    
    $('.canvas-navigate-scale').html(stroke);
    
    var height = parseInt($('.canvas-navigate-arrows').height()) + parseInt($('.canvas-wrap-navigate-scale').height()) + 30;
    
    $('.canvas-navigate').height(height);
}

// start actions
Instalation.prototype.startActions = function(){
    // load data from input field
    var canvas = this.canvas;
    var data = $('input[name='+inputForData+']').val();
    if(data != ''){
        canvas.loadFromJSON(data);
        setTimeout(function(){
            canvas.renderAll();
            
            canvas.forEachObject(function(o){
                if(o.type == 'image'){
                    calculateParam(canvas, o.width, o.height);
                }
                
                if(o.type == 'path'){
                    var newLeft = parseFloat(o.left) * parseFloat(o.scaleX);
                    var newTop  = parseFloat(o.top) * parseFloat(o.scaleY);
                    var points = o.path;
                    var path = new fabric.Path(points);
                    path.setFill('transparent');
                    path.setStroke('green');
                    path.fill_color = o.getFill();
                    o.remove();
                    instalation.canvas.add(path);
                    instalation.canvas.renderAll();
                }
            });
                        
        }, 1000);
    }
    
    // show panels for users
    if(userRole > 0){
        $('.canvas-container-left, .canvas-container-top').css({display : 'block'});
        if(userRole == 2){
            $('.monitor').css({display : 'table'});
        }
        this.canvas.calcOffset();
    }
}

// clear canvas
Instalation.prototype.clearCanvas = function(){
    this.clearCoordsOnCanvas();
    if(defaultBgImageUrl != ''){
        this.canvas.forEachObject(function(o){
            if(o.type != 'image'){
                o.remove();
            }
        });
    }
    else{
        this.canvas.clear();
    }
}
// get active-status from draw button
Instalation.prototype.getStatusDrawButton = function(elem){
    var element = $(elem).attr('data-active');
    return element;
}
// change active-status from draw button
Instalation.prototype.changeStatusDrawButton = function(elem){
    if($(elem).attr('data-active') == '0' || $(elem).attr('data-active') == undefined){
        $('#draw-figures button').each(function(){
            $(this).attr('data-active', '0');
        });
        $(elem).attr('data-active', '1');
    }
    else{
        $(elem).attr('data-active', '0');
    }
    this.canvas.forEachObject(function(o){
        delete o.currentProperty;
    });
}
// calculation of the parties draw rect
Instalation.prototype.calculationDrawRect = function(elem){
    
    var firstClick = $(elem).attr('data-coords');
    var tempArray = firstClick.split(',');
    
    var firstX = parseInt(tempArray[0]);
    var firstY = parseInt(tempArray[1]);
    
    var endX  = parseInt(mouseMove.x);
    var endY   = parseInt(mouseMove.y);
    
    if(firstX <= endX){
        var newWidth = endX - firstX;
    }else{
        var newWidth = firstX - endX;
        firstX = endX;
    }
    
    if(firstY <= endY){
        var newHeight = endY - firstY;
    }else{
        var newHeight = firstY - endY;
        firstY = endY;
    }
    
    return {firstX : firstX, firstY : firstY, newWidth : newWidth, newHeight : newHeight};
}
// clear coords in button for draw path
Instalation.prototype.clearCoordsOnCanvas = function(){
    $('#draw-figures button').each(function(){
        $(this).attr('data-coords', '');
    });
}
Instalation.prototype.clearCanvasOnDrawPath = function(type){
    this.canvas.forEachObject(function(o){
        if(o.currentProperty == type){
            o.remove();
        }
    });
}
// delete objects
Instalation.prototype.deleteObjects = function(){
    
    var element = this.canvas.getActiveObject();
    
    if(element != undefined || element != null){
        element.remove();
    }
    
    var elements = this.canvas.getActiveGroup();
    
    if(elements != undefined || elements != null){
        elements.forEachObject(function(o){
            o.remove();
        });
    }
        
    this.canvas.renderAll();
}
/*Instalation.prototype.insertInfoInCanvas = function(coords, isMouseDown){
    $('#'+canvasId).attr({'data-mousedown' : isMouseDown, 'data-mousedown-coords' : (!coords?'':coords.x+','+coords.y)}); 
}*/
Instalation.prototype.parseCoords = function(type){
     switch(type){
        case 'move':
            var coords = $('#'+canvasId).attr('data-move-coords').split(',');
            break;
        case 'down':
            var coords = $('#'+canvasId).attr('data-mousedown-coords').split(',');
            break;
     }
     
     return {x : coords[0], y : coords[1]};
}
Instalation.prototype.getCurrentColor = function(){
     return $('#form-picker #color').val();
}
Instalation.prototype.changeDefaultColor = function(){
     var currentColor = this.getCurrentColor();
     $('.canvas-current-color').css({backgroundColor : currentColor});
     
     var element = this.canvas.getActiveObject();
    
     if(element != undefined || element != null){
         element.set({fill : currentColor});
     }
     
     this.canvas.renderAll();
}
Instalation.prototype.accessData = function(){
    var currScale = $('#'+canvasId).attr('data-scale');
    
    move.scaleCanvas(false, 1);
    
    var data = JSON.stringify(this.canvas);
    
    $('input[name='+inputForData+']').val(data);
    
    move.scaleCanvas(false, currScale);
}
// scan objects for activity
Instalation.prototype.scanObjectsForActivity = function(){
    var element = this.canvas.getActiveObject();
    
    if(element != undefined || element != null){
        return true;
    }
    
    return false;
}
// scan objects for activity
Instalation.prototype.changeButtonActiveStatus = function(){
    $('.wrap-canvas-button').click(function(){
        $('.wrap-canvas-button').removeClass('wrap-canvas-active-button');
        var active = $(this).find('button').attr('data-active');
        if(active == 0){
            $(this).addClass('wrap-canvas-active-button');
        }
        else{
            $('button').attr('data-coords', '');
        }
    });
    
    return false;
}
Instalation.prototype.monitorParameters = function(type, points){
    switch(type){
        case 'down':
            mouseDown = mouseMove = {x : points.x, y : points.y};
            break;
        case 'up':
            mouseUp = {x : points.x, y : points.y};
            mouseDown = {x : 0, y : 0}
            break;
        case 'move':
            mouseMove = {x : points.x, y : points.y};
            break;
    }
    
    if(userRole < 2) return false;
    
    $('.mouse-down .for-content').text('x : ' + mouseDown.x + ', ' + ' y : ' + mouseDown.y);
    $('.mouse-up .for-content').text('x : ' + mouseUp.x + ', ' + ' y : ' + mouseUp.y);
    $('.mouse-move .for-content').text('x : ' + mouseMove.x + ', ' + ' y : ' + mouseMove.y);
}
Instalation.prototype.writeMouseEvents = function(){
    canvas = this.canvas;
    
    canvas.on('mouse:down', function(options){ 
        var points = canvas.getPointer(options.e);
        instalation.monitorParameters('down', points);
        $('#'+canvasId).attr({'data-mousedown' : '1', 'data-mousedown-coords' : points.x+','+points.y});
    })
    
    canvas.on('mouse:up', function(options){ 
        var points = canvas.getPointer(options.e);
        instalation.monitorParameters('up', points);
        $('#'+canvasId).attr({'data-mousedown' : '0', 'data-mousedown-coords' : ''});
    })
    
    
    canvas.on('mouse:move', function(options){ 
        if($('#'+canvasId).attr('data-mousedown') == '1'){
            var points = canvas.getPointer(options.e);
            instalation.monitorParameters('move', points);
            $('#'+canvasId).attr({'data-move-coords' : points.x+','+points.y});
            //var absolute = canvas.getAbsoluteCoords(points);
        }
    })
    
    canvas.on('object:added', function(options){ 
        fabric.Object.NUM_FRACTION_DIGITS = 15;
    })
    
}
// TEST METHODS
Instalation.prototype.getStack = function(){
    //alert(JSON.stringify(this.canvas));
    this.canvas.forEachObject(function(o){
        if(o.type == 'path'){
            //o.set({left : 245, top : 77});
            o.pathOffset;
        }
    });
    $('body').prepend(JSON.stringify(this.canvas));
}
Instalation.prototype.loadJ = function(){
    var data = $('input[name=json]').val();
    
    this.canvas.forEachObject(function(o){
        o.remove();
    });
    
    this.canvas.loadFromJSON(data);
        
    this.canvas.renderAll();
}
