//------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
//------------------------------------------------------------------------------
// Интервал запросов
var $EventsIntervalID = null;
//------------------------------------------------------------------------------
function CheckEvents(){
	//------------------------------------------------------------------------------
	if(!$EventsIntervalID){
		//------------------------------------------------------------------------------
		$EventsIntervalID = window.setInterval('CheckEvents();',5000);
		//------------------------------------------------------------------------------
		return null;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	var $HTTP = new HTTP();
	//------------------------------------------------------------------------------
	if(!$HTTP.Resource){
		//------------------------------------------------------------------------------
		alert('Не удалось создать HTTP соединение');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onLoaded = function(){
		//------------------------------------------------------------------------------
		$EventsIntervalID = window.setInterval('CheckEvents();',10000);
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onAnswer = function($Answer){
		//------------------------------------------------------------------------------
		$('link[rel$=icon]').remove();
		//------------------------------------------------------------------------------
		$('head').append($('<link rel="shortcut icon" type="image/x-icon"/>').attr('href','/favicon.ico?Messages=' + $Answer.Messages));
		//------------------------------------------------------------------------------
		//------------------------------------------------------------------------------
		switch($Answer.Status){
		case 'Empty':
			self.status = 'Нет новых событий';
			break;
		case 'Ok':
			//------------------------------------------------------------------------------
			self.status = 'События получены';
			//------------------------------------------------------------------------------
			var $Events = document.getElementById('Events');
			//------------------------------------------------------------------------------
			if(!$Events){
				//------------------------------------------------------------------------------
				$Events = document.createElement('DIV');
				//------------------------------------------------------------------------------
				$Events.id = 'Events';
				//------------------------------------------------------------------------------
				$Events.onclick = EventsDelete;
				//------------------------------------------------------------------------------
				//$eventsWidth = (document.body.clientWidth-100)/2;
				$eventsWidth = (document.body.clientWidth-100)*2/3;
				//------------------------------------------------------------------------------
				left = document.body.clientWidth - $eventsWidth - 2; // -2 added by lissyara
				//------------------------------------------------------------------------------
				// на мобильных разрешение может быть меньше 400.
				//$eventsWidth = $eventsWidth < 400 ? 400 : $eventsWidth;
				if(document.body.clientWidth < 400){
					//------------------------------------------------------------------------------
					left = 1;
					$eventsWidth = document.body.clientWidth - 2;
					//------------------------------------------------------------------------------
				}
				//------------------------------------------------------------------------------
				with($Events.style){
					//------------------------------------------------------------------------------
					cursor		= 'pointer';
					position	= 'absolute';
					top		= 0;
					left		= document.body.clientWidth - $eventsWidth - 2; // -2 added by lissyara, see jbs-17
					width		= $eventsWidth;
					maxHeight	= 100;
					overflow	= 'scroll';
					overflowX	= 'auto';
					border		= '1px solid #DCDCDC';
					backgroundColor	= '#FFFFFF';
					transition	= "opacity 0.1s";
					opacity		= 1;
					//------------------------------------------------------------------------------
				}
				//------------------------------------------------------------------------------
				document.body.appendChild($Events);
				//------------------------------------------------------------------------------
				var $Events = document.getElementById('Events');
				//------------------------------------------------------------------------------
				FadeIn($Events,80);
				//------------------------------------------------------------------------------
			}
			//------------------------------------------------------------------------------
			//------------------------------------------------------------------------------
			$Events.style.zIndex = GetMaxZIndex() + 1;
			//------------------------------------------------------------------------------
			for(var $i=0;$i<$Answer.Events.length;$i++){
				//------------------------------------------------------------------------------
				var $Event = $Answer.Events[$i];
				//------------------------------------------------------------------------------
				var $Text = SPrintF('<SPAN style="color:#990000;font-size:11px;font-weight:bold;">%s</SPAN><BR />%s',$Event.UserInfo,$Event.Text);
				//------------------------------------------------------------------------------
				$Div = document.createElement('DIV');
				//------------------------------------------------------------------------------
				$Div.innerHTML = $Text;
				//------------------------------------------------------------------------------
				switch($Event.PriorityID){
				case 'Warning':
					var $Color = '#FFC0CB';
					break;
				case 'Error':
					var $Color = '#EE82EE';
					break;
				case 'Notice':
					//var $Color = '#FFD700';
					var $Color = '#F1FCCE';
					break;
				case 'Billing':
					//var $Color = '#D5F66C';
					var $Color = '#F1FCCE';
					break;
				case 'System':
					//var $Color = '#ADC1F0';
					var $Color = '#F1FCCE';
					break;
				case 'Hosting':
					var $Color = '#F1FCCE';
					break;
				default:
					var $Color = '#FFCCCC';
				}
				//------------------------------------------------------------------------------
				with($Div.style){
					//------------------------------------------------------------------------------
					borderBottom    = '1px solid #DCDCDC';
					backgroundColor = $Color;
					padding         = '5px';
					//------------------------------------------------------------------------------
				}
				//------------------------------------------------------------------------------
				$Events.appendChild($Div);
				//------------------------------------------------------------------------------
				$Events.scrollTop = 9999999;
				//------------------------------------------------------------------------------
			}
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		default:
			// No more...
		}
		//------------------------------------------------------------------------------
	};
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	if(!$HTTP.Send('/API/Events'))
		return false;
	//------------------------------------------------------------------------------
	self.status = 'Получение новых событий...';
	//------------------------------------------------------------------------------
	window.clearInterval($EventsIntervalID);
	//------------------------------------------------------------------------------
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function EventsDelete(){
	//------------------------------------------------------------------------------
	$Events = document.getElementById('Events');
	//------------------------------------------------------------------------------
	// если ничего не выбрано - закрыть
	if(!getSelection().toString()){
		//------------------------------------------------------------------------------
		$Events.style.opacity = 0;
		//------------------------------------------------------------------------------
		setTimeout("$Events.parentNode.removeChild($Events);",100);
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function EventsReaded(){
	//------------------------------------------------------------------------------
	var $Form = document.forms['TableSuperForm'];
	//------------------------------------------------------------------------------
	$HTTP = new HTTP();
	//------------------------------------------------------------------------------
	if(!$HTTP.Resource){
		//------------------------------------------------------------------------------
		alert('Не удалось создать HTTP соединение');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onLoaded = function(){
		//------------------------------------------------------------------------------
		HideProgress();
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onAnswer = function($Answer){
		//------------------------------------------------------------------------------
		switch($Answer.Status){
		case 'Error':
			ShowAlert($Answer.Error.String,'Warning');
			break;
		case 'Exception':
			ShowAlert(ExceptionsStack($Answer.Exception),'Warning');
			break;
		case 'Ok':
			GetURL(document.location);
			break;
		default:
			alert('Неизвестный ответ');
		}
		//------------------------------------------------------------------------------
	};
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	var $Args = FormGet($Form);
	//------------------------------------------------------------------------------
	if(!$HTTP.Send('/API/EventsReaded',$Args)){
		//------------------------------------------------------------------------------
		alert('Не удалось отправить запрос на сервер');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	ShowProgress('События помечаются как прочитанные');
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

