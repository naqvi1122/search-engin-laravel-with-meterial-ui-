<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\post;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

//use Alimranahmed\LaraOCR\Services\OcrAbstract;

class PostController extends Controller
{

    //.................simple search....................................

    public function show(Request $request)
    {
        $search = $request['search'] ?? "";
        if ($search != "") {
            //where
            $project = post::where('id', 'like', '%' . $request->search . '%')
                ->orwhere('name', 'like', '%' . $request->search . '%')
                ->orwhere('mandate_file', 'like', '%' . $request->search . '%')->get();

        } else {

            $project = post::all();
        }
        $data = compact('project', 'search');
        return view('display')->with($data);

    }

//.................................autocomplete................

    public function autocompleteSearch(Request $request)
    {
        $query = $request->get('query');
        $filterResult = post::where('name', 'LIKE', '%' . $query . '%')->get();
        return response()->json($filterResult);
    }

//.............................student and address relation  one to one ......................

//....................................................pdf search......................................
    public function pdf(Request $request)
    {
        return view('pdfsearch');
    }

//...............................................convert pdf and store to database................................
    public function pdfstore(Request $request)
    {

        $data = new File();

        $file = $request->file;
        //.....................................ppt file convert..................................
        //    $zip_handle = new ZipArchive;
        //     $output_text = "";
        //     if(true === $zip_handle->open($file)){
        //         $slide_number = 1; //loop through slide files
        //         while(($xml_index = $zip_handle->locateName("ppt/slides/slide".$slide_number.".xml")) !== false){
        //             $xml_datas = $zip_handle->getFromIndex($xml_index);
        //             $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        //             $output_text .= strip_tags($xml_handle->saveXML());
        //             $slide_number++;
        //         }
        //         if($slide_number == 1){
        //             $output_text .="";
        //         }
        //         $zip_handle->close();
        //     }else{
        //     $output_text .="";
        //     }
        //dd($output_text);

//.......................................ppt file convert end.................................
        //...................................................word file convert.....................
        //  $kv_texts = '';
        //  $kv_strip_texts = '';
        //  $zip = zip_open($file);

        //  if (!$zip || is_numeric($zip)) return false;

        //  while ($zip_entry = zip_read($zip)) {

        //      if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

        //      if (zip_entry_name($zip_entry) != "word/document.xml") continue;

        //      $kv_texts .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

        //      zip_entry_close($zip_entry);
        //  }

        //  zip_close($zip);
        //  $kv_texts = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $kv_texts);
        // $kv_texts = str_replace('</w:r></w:p>', "\r\n", $kv_texts);
        // $kv_strip_texts = nl2br(strip_tags($kv_texts));

//..............................................end word file convert...................................

        //..........................xl file convert...........................................
        //  $xl = Excel::toArray([],$file);
        //  $y=json_encode($xl);
        //dd($y);
        //......................................xl file convert end.......................................
        $fileName = time() . '_' . $file->getClientOriginalName();
        //............................pdf file convert...................................................
        $pdfParser = new Parser();
        $pdf = $pdfParser->parseFile($file->path());
        $content = $pdf->getText();
        //................................end pdf convert......................................................
        //  $request->file->move('assests',$fileName);
        Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($request->file), 'public');
        $url = Storage::disk('spaces')->url('uploads/' . $fileName);
        $data->file = $url;

        $data->name = $content;
        $data->description = $request->description;
        $data->save();
        return redirect()->back();
        //return $data;

    }

//...................................................end convert pdf and store database function...........................

//................................................view and search result and compare it with search value.....................
    public function view(Request $request)
    {
        $search = $request['search'] ?? "";
        if ($search != "") {
            //where
            $data = File::where('id', 'like', '%' . $request->search . '%')
                ->orwhere('name', 'like', '%' . $request->search . '%')
                ->orwhere('file', 'like', '%' . $request->search . '%')->get();

        } else {

            $data = File::all();

        }
        $p = compact('data', 'search');
        return view('upload_data')->with($p);

    }

//................................................end view and search.............................................................
    //.................................................download file function................................................
    public function wq(Request $request, $file)
    {

        return response()->download(public_path('assests/' . $file));
    }

//.............................................end download file function.....................................................

//...........................................view pdf file function..........................................................
    public function view_pdf($id)
    {

        $data = File::find($id);
        return view('viewpdf', compact('data'));
    }

    //......................................................end view file function...........................................

//...................................................filesearch.............................................................
    public function filesearch(Request $request)
    {
        if (($request->search) == null) {
            return view('pages.table_list');
        }
        $data = File::where('id', 'like', '%' . $request->search . '%')
            ->orwhere('name', 'like', '%' . $request->search . '%')
            ->orwhere('file', 'like', '%' . $request->search . '%')
            ->orwhere('description', 'like', '%' . $request->search . '%')->get();
        $q = $request->search;
        if (count(array($data)) > 0) {
            //dd($opinions_pacra);
            $data = collect($data);

            return view('pages.table_list')->with('data', $data)
                ->with('query', $q);
        } else {
            //  dd(count($opinions));
            //return withFlashSuccess(trans('No Record Found'));
            return view('pages.table_list')->withMessage('The code you provided is not existing.');
        }

    }

//..........................................................end filesearch....................................................

//...........................................................wicpac 1st table api...........................................
    public function apitestt(Request $request)
    {
        $qe = $request->search;
        //dd($qe);

        $info = Http::get('http://127.0.0.1:8080/api/wispacdata/' . $qe)->json();

        $z = Http::get('http://127.0.0.1:8080/api/wispacdata_table2/' . $qe)->json();
        $third_table = Http::get('http://127.0.0.1:8080/api/wispacdata_table3/' . $qe)->json();

        if (count(array($info)) > 0 || count($z) > 0 || count($third_table) > 0) {
            //dd($opinions_pacra);
            $info = collect($info);
            $z = collect($z);
            $third_table = collect($third_table);

            return view('pages.typography')->with('info', $info)->with('z', $z)->with('third_table', $third_table);

        } else {

            return view('pages.typography')->withMessage('The code you provided is not existing.');
        }

    }
    //......................................................end 1st table api//...............................................

    public function crowler()
    {
        $main_url = "https://wizpac.sgp1.cdn.digitaloceanspaces.com/uploads/1659005795_php_tutorial.pdf";
        //$str = file_get_contents($main_url);

        $url = Storage::disk('spaces')->allFiles('uploads');
        dd($url);
        $newurl = "https://wizpac.sgp1.cdn.digitaloceanspaces.com/" . $url[8];
        $contents = Storage::disk('spaces')->get($url[8]);
//dd($newurl);

        //$section = file_get_contents($url[8], FALSE, NULL, 20, 14);
        //$r = Storage::get($newurl)->disk('spaces');
        //$phpWord = \PhpOffice\PhpWord\IOFactory::load($url[8]);

        //$y=json_encode($contents);
        //$o=Storage::get($newurl);
        //$o=readfile(preg_replace("/ /", "%20", $newurl));
        //$url = preg_replace("/ /", "%20", $newurl);

//    $docFile = $newurl;
        //    $doctotext = new DocToText($docFile);
        //    $doctotext->getText();
        // $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        //         $content = str_replace('</w:r></w:p>', "\r\n", $content);
        //         $striped_content = strip_tags($content);

//dd($contents);

//    $data =Excel::toArray([], $url[8], 'spaces');
        //$y=json_encode($data);

        for ($x = 6; $x <= 7; $x++) {
            $url = Storage::disk('spaces')->allFiles('uploads');
            //dd($url);
            $newurl = "https://wizpac.sgp1.cdn.digitaloceanspaces.com/" . $url[$x];
            $foo = \File::extension($newurl);
            //dd($foo);
            if ($foo == 'pdf') {

                $pdfParser = new Parser();
                $pdf = $pdfParser->parseFile($newurl);
                $content = $pdf->getText();
                $data = new File();
                $data->file = $url[$x];
                $data->name = $content;
                $data->description = $newurl;
                $data->save();
            }
            if ($foo == 'xls') {
                $xl = Excel::toArray([], $url[$x], 'spaces');
                $y = json_encode($xl);
                $data = new File();
                $data->file = $url[$x];
                $data->name = $y;
                $data->description = $newurl;
                $data->save();

            }
            if ($foo == 'txt') {
                $textdata = Storage::disk('spaces')->get($url[$x]);
                $data = new File();
                $data->file = $url[$x];
                $data->name = $textdata;
                $data->description = $newurl;
                $data->save();

            }

        }

    }

}
