<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../api/config.php';require __DIR__.'/../lib/admin-orders.php';
function verify(bool $v,string $message): void {if(!$v)throw new RuntimeException($message);}
function request(string $path,?array $data=null,string $cookie=''): array {
 $ch=curl_init('http://localhost/SOLID/'.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIE=>$cookie]);if($data!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($data),CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);$raw=curl_exec($ch);if($raw===false)throw new RuntimeException(curl_error($ch));$size=curl_getinfo($ch,CURLINFO_HEADER_SIZE);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$head=substr($raw,0,$size);$body=substr($raw,$size);preg_match_all('/Set-Cookie: (PHPSESSID=[^;]+)/i',$head,$m);return ['status'=>$status,'json'=>json_decode($body,true),'cookie'=>($m[1]?end($m[1]):$cookie),'body'=>$body,'headers'=>$head];
}
$db=database();$uid=0;$pid=0;$cookie='';$email='admin-test-'.bin2hex(random_bytes(6)).'@example.invalid';$password=bin2hex(random_bytes(16));
try{
 $db->prepare("INSERT INTO users(name,email,password_hash,phone,zip,address,address_number,neighborhood,city,is_admin) VALUES('TESTE TEMPORÁRIO',?,?,'','','','','','',1)")->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);$uid=(int)$db->lastInsertId();
 $p=['sku'=>'ADMIN-TEST','name'=>'TESTE TEMPORÁRIO','description'=>'Teste removido ao finalizar','price'=>50.25,'stock'=>10,'sizes'=>['M'],'colors'=>['Preto'],'images'=>['../assets/images/products/M1.jpeg']];$db->prepare('INSERT INTO products(data) VALUES(?)')->execute([json_encode($p)]);$pid=(int)$db->lastInsertId();
 verify(request('admin/api.php')['status']===403,'Anonymous API access allowed');verify(request('admin/')['status']===302,'Anonymous panel access allowed');
 $r=request('api/account.php',['action'=>'login','email'=>$email,'password'=>$password]);verify($r['status']===200,'Login failed');$cookie=$r['cookie'];
 $r=request('admin/api.php',null,$cookie);verify($r['status']===200,'Admin GET failed: '.$r['body']);$token=$r['json']['token'];
 verify(request('admin/api.php',['action'=>'manual','token'=>'bad'],$cookie)['status']===403,'Invalid CSRF accepted');
 $siteToken=request('api/orders.php',null,$cookie)['json']['token'];
 $site=['token'=>$siteToken,'requestKey'=>bin2hex(random_bytes(16)),'customer'=>['name'=>'TESTE TEMPORÁRIO','phone'=>'11999999999','email'=>'site@example.invalid','address'=>'Rua teste','number'=>'1','neighborhood'=>'Centro','zip'=>'01001000','city'=>'São Paulo / SP','delivery'=>'Entrega','payment'=>'Pix'],'items'=>[['id'=>$pid,'size'=>'M','color'=>'Preto','quantity'=>1,'price'=>0.01]]];
 $r=request('api/orders.php',$site,$cookie);verify($r['status']===200,'Site checkout failed: '.$r['body']);$siteId=(int)$r['json']['order']['id'];$siteCode=$r['json']['order']['code'];
 $r=request('admin/api.php?origin=site&q='.$siteCode,null,$cookie);verify(count($r['json']['orders'])===1&&(int)$r['json']['orders'][0]['id']===$siteId&&$r['json']['orders'][0]['payment_status']==='pending'&&(int)$r['json']['orders'][0]['total_cents']===5025,'Site order missing or trusted client price/payment');
 adminUpdateOrder($db,$siteId,$uid,['status'=>'cancelled','confirm_cancel'=>true]);

 $data=['action'=>'manual','token'=>$token,'requestKey'=>bin2hex(random_bytes(16)),'customer'=>['name'=>'TESTE TEMPORÁRIO','phone'=>'11999999999','email'=>'','address'=>'Rua teste','zip'=>'01001000','city'=>'São Paulo','state'=>'SP','payment'=>'Pix'],'items'=>[['id'=>$pid,'size'=>'M','color'=>'Preto','quantity'=>2,'price'=>'50.25']],'discount'=>'5.00','shipping'=>'10.00','payment_status'=>'pending','status'=>'pending'];
 $r=request('admin/api.php',$data,$cookie);verify($r['status']===200,'Manual creation failed: '.$r['body']);$id=(int)$r['json']['id'];
 verify((int)request('admin/api.php',$data,$cookie)['json']['id']===$id,'Idempotency failed');
 $get=function()use($db,$id){$q=$db->prepare('SELECT * FROM orders WHERE id=?');$q->execute([$id]);return $q->fetch();};$stock=function()use($db,$pid){$q=$db->prepare('SELECT data FROM products WHERE id=?');$q->execute([$pid]);return json_decode($q->fetchColumn(),true)['stock'];};
 verify((int)$get()['total_cents']===10550&&$get()['payment_status']==='pending'&&$get()['origin']==='whatsapp','Incorrect totals or origin/payment');verify($stock()===8,'Manual stock failed');
 $edit=$data;$edit['id']=$id;$edit['revision']=1;$edit['items'][0]['quantity']=3;$r=request('admin/api.php',$edit,$cookie);verify($r['status']===200,'Manual edit failed');verify($stock()===7,'Edit stock delta failed');verify(request('admin/api.php',$edit,$cookie)['status']===422,'Stale revision accepted');
 $bad=$data;$bad['requestKey']=bin2hex(random_bytes(16));$bad['items'][0]['quantity']=11;verify(request('admin/api.php',$bad,$cookie)['status']===422,'Overselling accepted');verify($stock()===7,'Rejected operation modified stock');
 $status=['action'=>'status','token'=>$token,'id'=>$id,'revision'=>2,'status'=>'delivered'];verify(request('admin/api.php',$status,$cookie)['status']===422,'Invalid status transition accepted');
 $status['status']='confirmed';verify(request('admin/api.php',$status,$cookie)['status']===200,'Confirmation failed');verify($get()['payment_status']==='pending','Order confirmation incorrectly approved payment');
 $status['revision']=3;$status['payment_status']='paid';verify(request('admin/api.php',$status,$cookie)['status']===200,'Payment update failed');
 $r=request('admin/api.php?origin=whatsapp&q=TESTE%20TEMPOR%C3%81RIO&id='.$id,null,$cookie);verify($r['status']===200&&count($r['json']['history'])>=4&&(int)$r['json']['metrics']['revenue']===15575,'Filters/history failed');
 $status['revision']=4;$status['status']='cancelled';verify(request('admin/api.php',$status,$cookie)['status']===422,'Cancellation without confirmation accepted');$status['confirm_cancel']=true;verify(request('admin/api.php',$status,$cookie)['status']===200,'Cancellation failed');verify($stock()===10,'Cancellation stock restore failed');
 $status['revision']=5;verify(request('admin/api.php',$status,$cookie)['status']===200&&$stock()===10,'Repeated cancel restocked twice');
 $r=request('admin/api.php?origin=whatsapp&q=TESTE%20TEMPOR%C3%81RIO',null,$cookie);verify((int)$r['json']['metrics']['revenue']===0,'Cancelled paid order counted as revenue');
 $html=request('admin/',null,$cookie);verify($html['status']===200&&str_contains($html['body'],'manifest.json')&&str_contains($html['headers'],'no-store'),'Authenticated panel failed');
 $db->prepare('UPDATE users SET is_admin=0 WHERE id=?')->execute([$uid]);verify(request('admin/api.php',null,$cookie)['status']===403,'Revoked admin retained API access');verify(request('admin/',null,$cookie)['status']===302,'Revoked admin retained page access');
 echo "PASS: HTTP login, anonymous/non-admin access, CSRF, manual create/edit, totals, origin, idempotency, stock, stale revisions, workflow/payment separation, history, filters, cancellation and protected panel.\n";
}finally{
 if($cookie)request('api/account.php',['action'=>'logout'],$cookie);
 if($uid){$db->prepare('DELETE FROM order_history WHERE order_id IN (SELECT id FROM orders WHERE user_id=?)')->execute([$uid]);$db->prepare('DELETE FROM admin_events WHERE user_id=?')->execute([$uid]);$db->prepare('DELETE FROM orders WHERE user_id=?')->execute([$uid]);$db->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);}if($pid)$db->prepare('DELETE FROM products WHERE id=?')->execute([$pid]);
}
