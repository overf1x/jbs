<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#Debug(SPrintF('[comp/Edesks/Text]: Params = %s',print_r($Params,true)));
$IsLockText = FALSE;
#-------------------------------------------------------------------------------
if(IsSet($Params['IsLockText']))
	if($Params['IsLockText'])
		$IsLockText = TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# если это сообщение для почты, то скрытый текст надо убрать
if(IsSet($Params['IsEmail']))
	$IsLockText = FALSE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$String = Str_Replace(Chr(7),'',SPrintF("%s\n",$Params['String']));
#-------------------------------------------------------------------------------
// для сообщений уходящих юзеру не надо вырезать html символы
if(!IsSet($Params['IsEmail']))
	$String = Str_Replace('&quot;','"',HtmlSpecialChars($String));
#-------------------------------------------------------------------------------
# $String = Preg_Replace('/(http:\/\/[\/a-zA-Z0-9\.\-\_]+)/su','<A href="\\1" target="blank">\\1</A>',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[hidden\](.+)\[\/hidden\]/sU',$IsLockText?'<DIV class="LockText"><B style="font-size:11px;">Скрытый текст:<BR /></B>\\1</DIV>':'',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[quote\](.+)\[\/quote\]/sU',!IsSet($Params['IsEmail'])?'<DIV class="QuoteText"><!-- <B style="font-size:11px;">Цитата:</B> -->\\1</DIV>':'\\1',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[color=([a-z]+)\](.+)\[\/color\]/sU',!IsSet($Params['IsEmail'])?'<SPAN style="color:\\1;">\\2</SPAN>':'\\2',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[size=([0-9]+)\](.+)\[\/size\]/sU',!IsSet($Params['IsEmail'])?'<SPAN style="font-size:\\1px;">\\2</SPAN>':'\\2',$String);
#-------------------------------------------------------------------------------
# JBS-697
$String = Preg_Replace("#(^|\s|>)((http://)([a-zA-Z0-9][a-zA-Z0-9-]*\.)+([a-zA-Z]{2,4})(:\d{2,5})?(/[a-zA-Z/.0-9_]*)?(jpg|jpeg|gif|png|bmp)\b)#i","\\1[image]\\2[/image]",$String);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($GLOBALS['__USER']['Params']['Settings']['EdeskImagesPreview'] == "No" || IsSet($Params['IsEmail'])){
	#-------------------------------------------------------------------------------
	$String = Preg_Replace("/\[image\](http|ftp|https):\/\/(.+)\[\/image\]/sU",'\\1://\\2',$String);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$String = Preg_Replace("/\[image\](http|ftp|https):\/\/(.+)\[\/image\]/sU","<img class=\"TicketSmall\" src=\"\\1://\\2\" />",$String);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$String = preg_replace( "#(^|\s|>)((http|https|news|ftp)://\w+[^\s\[\]\<]+)#i", !IsSet($Params['IsEmail'])?"\\1<A href=\"\\2\" target=\"blank\">\\2</A>":'\\1\\2',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[link](.+)\[\/link\]/sU',!IsSet($Params['IsEmail'])?'<A href="\\1" target="blank">\\1</A>':'\\1',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace( "(\[link\=[\"']?((http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?)[\"']?\](.+?)\[/link\])", IsSet($Params['IsEmail'])?"$5 $1":"<a target=\"blank\" href=\"$1\">$5</a>", $String );
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[b](.+)\[\/b\]/sU',!IsSet($Params['IsEmail'])?'<B>\\1</B>':'\\1',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[i](.+)\[\/i\]/sU',!IsSet($Params['IsEmail'])?'<I>\\1</I>':'\\1',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[p](.+)\[\/p\]\n/sU',!IsSet($Params['IsEmail'])?'<P>\\1</P>':'\\1',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[marker](.+)\[\/marker\]\n/sU',!IsSet($Params['IsEmail'])?'<TABLE  style="margin-left:15px;" cellspacing="2" cellpadding="2"><TR><TD valign="top" style="padding-top:5px;"><IMG alt="+" width="8px" height="8px" src="/styles/root/Images/Ul.png" /></TD><TD>\\1</TD></TR></TABLE>':'\\1',$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\\n(--|---)\n/sU',!IsSet($Params['IsEmail'])?"\n<HR align=\"left\" size=\"1\" />\n":"\n--\n",$String);
#-------------------------------------------------------------------------------
$String = Preg_Replace('/\[bg=([a-z]+)\](.+)\[\/bg\]/sU',!IsSet($Params['IsEmail'])?'<DIV style="padding:5px;background-color:\\1;">\\2</DIV>':'\\2',$String);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!IsSet($Params['IsEmail'])){
	#-------------------------------------------------------------------------------
	$Smiles = System_XML('config/Smiles.xml');
	#-------------------------------------------------------------------------------
	if(Is_Error($Smiles))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Smiles) as $SmileID){
		#-----------------------------------------------------------------------------
		$Smile = $Smiles[$SmileID];
		#-----------------------------------------------------------------------------
		$String = Str_Replace($Smile['Pattern'],SPrintF('<IMG alt="%s" src="%s" style="display:inline;" />',$Smile['Name'],SPrintF('SRC:{Images/Smiles/%s.gif}',$SmileID)),$String);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(StrLen(Trim($String)) < 1)
	$String = !IsSet($Params['IsEmail'])?'<FONT color="gray">Извините, но это сообщение было удалено автором до того как вы его прочитали</FONT>':'Пустое сообщение';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!IsSet($Params['IsEmail']))
	$String = Str_Replace("\n",'<BR />',Trim($String));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $String;
#-------------------------------------------------------------------------------

?>
