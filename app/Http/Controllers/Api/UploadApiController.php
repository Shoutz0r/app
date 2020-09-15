<?php

namespace App\Http\Controllers\Api;

use App\Events\Internal\UploadAddedEvent;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessUpload;
use App\Upload;
use Illuminate\Http\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UploadApiController extends Controller {

    public function store(Request $request) {
        //Check if a file has been provided with the request
        if($request->hasFile('media') !== true) {
            return response()->json(['error' => 'No file with name media uploaded'], 400);
        }

        //Check if there are any errors with the file upload
        if($request->file('media')->isValid() !== true) {
            return response()->json(['error' => 'The uploaded file did not upload correctly'], 400);
        }

        //Get the name and extension of the file
        $name   = $request->file('media')->getClientOriginalName();
        $ext    = $request->file('media')->extension();

        //TODO check if the file is a valid media file.

        //Set the new  name for the file
        $newName     = time().$name.'.'.$ext;

        //Move the file to a temporary directory while it's awaiting processing.
        $request->file('media')->storeAs(Upload::STORAGE_PATH, $newName);

        //TODO sanitize the uploaded file. ie: remove all metadata to prevent possible exploits.

        //Store the upload in the database for use in the Job
        $upload = new Upload();
        $upload->filename   = $newName;
        //$upload->user_id    = $request->user()->id;
        $upload->user_id    = 1;
        $upload->status     = Upload::STATUS_QUEUED;
        $upload->save();

        //Send the event that an upload has been added
        app(EventDispatcher::class)->dispatch(new UploadAddedEvent($upload));

        //Add the Upload as a job to the Queue for processing
        //ProcessUpload::dispatch($upload)->onConnection('database_' . Upload::QUEUE_NAME)->onQueue(Upload::QUEUE_NAME);
        ProcessUpload::dispatch($upload)->onQueue(Upload::QUEUE_NAME);

        return response()->json(['message' => 'Upload queued for processing'], 200);
    }

}
