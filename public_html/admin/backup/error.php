<?php
function error_404($request, $params=array())
{
    $view = SOS::loadClass('SOS_View');
    $view->display('error_404.php');
}

function error_403($request, $params=array())
{
    $user = SOS::loadClass('SOS_User');
    if(substr(SOS::$uri, 0, 5) == 'admin')
    {
        $r = urlencode(SOS::$uri.'?'.$request->server['QUERY_STRING']);
        @header('Location:'.BASEURL.'admin/login?r='.$r);exit;
    }
    if($request->isPost())
    {
        $user->login($request->post('username'), $request->post('password'), TRUE, BASEURL.SOS::$uri);
    }
    if($user->isLoggedIn())
    {
        echo 'You have no permissions';
    }else
    {
        @header('Location:'.BASEURL.'?r='.SOS::$uri);exit;
    }
}
?>