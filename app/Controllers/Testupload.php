<?php

namespace App\Controllers;

class Testupload extends BaseController
{
    public function index()
    {
        return view('upload_form', ['error' => ' ']);
    }

    public function do_upload()
    {
        $this->validate([
            'userfile' => [
                'uploaded[userfile]',
                'max_size[userfile,100]',
                'mime_in[userfile,image/png,image/jpg,image/gif]',
                'ext_in[userfile,png,jpg,gif]',
                'max_dims[userfile,1024,768]',
            ],
        ]);

        $file = $this->request->getFile('userfile');
		$originalName = $file->getClientName();
		
        if (! $path = $file->store('/Registration/',$originalName)) {
            return view('upload_form', ['error' => 'upload failed']);
        }
        $data = ['upload_file_path' => $path];

        return view('upload_success', $data);
    }
}