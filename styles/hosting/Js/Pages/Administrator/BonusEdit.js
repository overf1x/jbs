//------------------------------------------------------------------------------
/** @autho Alex Keda, for www.host-food.u */
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function BonusEdit(){
  //----------------------------------------------------------------------------
  var $Form = document.forms['BonusEditForm'];
  //----------------------------------------------------------------------------
  $HTTP = new HTTP();
  //----------------------------------------------------------------------------
  if(!$HTTP.Resource){
    //--------------------------------------------------------------------------
    alert('Не удалось создать HTTP соединение');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  $HTTP.onLoaded = function(){
    //--------------------------------------------------------------------------
    HideProgress();
  }
  //----------------------------------------------------------------------------
  $HTTP.onAnswer = function($Answer){
    //--------------------------------------------------------------------------
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
        alert('Не известный ответ');
    }
  }
  //----------------------------------------------------------------------------
  var $Args = FormGet($Form);
  //----------------------------------------------------------------------------
  if(!$HTTP.Send('/Administrator/API/BonusEdit',$Args)){
    //--------------------------------------------------------------------------
    alert('Не удалось отправить запрос на сервер');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  ShowProgress($Form.BonusID?'Редактирование бонуса':'Добавление бонуса');
}
