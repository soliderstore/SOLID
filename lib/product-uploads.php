<?php
declare(strict_types=1);

const PRODUCT_UPLOAD_DIRECTORY = __DIR__ . '/../assets/images/uploads';
const PRODUCT_UPLOAD_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

function isProductUploadFilename(string $filename): bool {
    return preg_match('/\A[a-f0-9]{32}\.(?:jpe?g|png|webp|gif)\z/i', $filename) === 1;
}

function productUploadUrl(string $siteRoot, string $filename): string {
    return $siteRoot . '/api/product-image.php?file=' . rawurlencode($filename);
}

function orderedProductImages(array $references, string $orderJson): array {
    $order=json_decode($orderJson,true);
    if(!is_array($order)||!array_is_list($order)||count($order)!==count($references))throw new RuntimeException('A ordem das fotos é inválida. Selecione as fotos novamente.');
    $images=[];$seen=[];
    foreach($order as $key){
        if(!is_string($key)||!isset($references[$key])||isset($seen[$key]))throw new RuntimeException('A ordem das fotos é inválida.');
        $seen[$key]=true;$images[]=$references[$key];
    }
    return $images;
}
function productImageUploads(array $files, string $directory): array {
    $names=$files['name']??[];
    if(!is_array($names)||count($names)>20)throw new RuntimeException('Selecione até 20 imagens.');
    $validated=[];$saved=[];
    $types=PRODUCT_UPLOAD_TYPES;
    foreach($names as $index=>$name){
        $error=$files['error'][$index]??UPLOAD_ERR_NO_FILE;
        if($error===UPLOAD_ERR_NO_FILE)continue;
        if($error!==UPLOAD_ERR_OK)throw new RuntimeException('Não foi possível enviar uma imagem. Confira o limite de tamanho e tente novamente.');
        $tmp=$files['tmp_name'][$index]??'';
        if(!is_string($tmp)||!is_uploaded_file($tmp))throw new RuntimeException('Arquivo de imagem inválido.');
        if(filesize($tmp)>5*1024*1024)throw new RuntimeException('Cada imagem pode ter no máximo 5 MB.');
        $info=@getimagesize($tmp);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if(!$info||!isset($types[$mime])||$info['mime']!==$mime||$info[0]>12000||$info[1]>12000)throw new RuntimeException('Escolha imagens JPG, PNG, WebP ou GIF de até 12000 pixels por lado.');
        $validated[]=[$tmp,bin2hex(random_bytes(16)).'.'.$types[$mime]];
    }
    try{
        if($validated&&!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
        foreach($validated as [$tmp,$name]){
            $destination=$directory.'/'.$name;
            if(!move_uploaded_file($tmp,$destination))throw new RuntimeException('Não foi possível salvar a imagem. Tente novamente.');
            $saved[]=$destination;
        }
        return $saved;
    }catch(Throwable $e){foreach($saved as $file)if(is_file($file))unlink($file);throw $e;}
}
