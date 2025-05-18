
<?php
namespace App\Http\Controllers;

use App\Models\TeklifTalebi;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\TalepMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Tedarikci;




class AdminTeklifController extends Controller
{
    public function index()
    {
        $talepler = TeklifTalebi::with('urunler')->orderBy('tarih', 'desc')->get();
        // dd($talepler);
        return view('admin.teklif-talebi.index', compact('talepler'));
    }

  
    public function create()
    {
      $talepler = TeklifTalebi::with('urunler')->orderBy('tarih','desc')->get();
      return view('admin.teklif-talebi.create');
    }


    public function store(Request $request)
    {
        // dd($request->all());
        $data = $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'son_teslim_tarihi' => 'nullable|date',
            'urunler' => 'required|array|min:1',
            'urunler.*.urun_adi' => 'required|string|max:255',
            'urunler.*.birim' => 'required|string|max:50',
            'urunler.*.miktar' => 'required|numeric|min:0',
        ]);
    
        
        $data['tarih'] = now();
    
     
        \DB::transaction(function() use ($data) {
            
            $teklifTalebi = TeklifTalebi::create([
                'baslik' => $data['baslik'],
                'aciklama' => $data['aciklama'] ?? null,
                'son_teslim_tarihi' => $data['son_teslim_tarihi'] ?? null,
                'tarih' => $data['tarih'],
            ]);
    
            foreach ($data['urunler'] as $urun) {
                $teklifTalebi->urunler()->create($urun);
            }
        });


    
        return redirect()->back()->with('success', 'Teklif talebi ve ürünler başarıyla oluşturuldu.');

        //  Tüm tedarikçilere e-posta gönder
    $urunler = $data['urunler'];
    $sonTeslimTarihi = $data['son_teslim_tarihi'] ?? null;
    $talepId = $teklifTalebi->id;

    $tedarikciler = Tedarikci::all();

    foreach ($tedarikciler as $tedarikci) {
        Mail::to($tedarikci->eposta)->send(new TalepMail($urunler, $sonTeslimTarihi, $talepId));
    }

    // return redirect()->back()->with('success', 'Teklif talebi ve ürünler başarıyla oluşturuldu.');
    }
    

    public function show($id)
    {
        $talep = TeklifTalebi::with('urunler')->findOrFail($id);
        return view('admin.teklif-talebi.show', compact('talep'));
    }

    public function edit($id)
    {
        $talep = TeklifTalebi::with('urunler')->findOrFail($id);
        return view('admin.teklif-talebi.edit', compact('talep'));
    }
    
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'baslik' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'son_teslim_tarihi' => 'nullable|date',
            'urunler' => 'required|array|min:1',
            'urunler.*.urun_adi' => 'required|string|max:255',
            'urunler.*.birim' => 'required|string|max:50',
            'urunler.*.miktar' => 'required|numeric|min:0',
        ]);
    
        \DB::transaction(function () use ($data, $id) {
            $talep = TeklifTalebi::findOrFail($id);
            $talep->update([
                'baslik' => $data['baslik'],
                'aciklama' => $data['aciklama'] ?? null,
                'son_teslim_tarihi' => $data['son_teslim_tarihi'] ?? null,
            ]);
    
            // Önce mevcut ürünleri sil
            $talep->urunler()->delete();
    
            // Sonra yeni ürünleri kaydet
            foreach ($data['urunler'] as $urun) {
                $talep->urunler()->create($urun);
            }
        });
    
        return redirect()->route('admin.talepler.index')->with('success', 'Teklif talebi güncellendi.');
    }

    public function destroy($id)
    {
        \DB::transaction(function () use ($id) {
            $talep = TeklifTalebi::findOrFail($id);
            $talep->urunler()->delete();
            $talep->delete();
        });
    
        return redirect()->route('admin.talepler.index')->with('success', 'Talep silindi.');
    }

    public function pdf($id)
{
    $talep = TeklifTalebi::with([
        'urunler',
        'cevaplar.urunler',
        'cevaplar.tedarikci'
    ])->findOrFail($id);

    $secilenCevap = $talep->cevaplar->firstWhere('kabul_edildi_mi', true);

    if (!$secilenCevap) {
        return back()->with('error', 'Henüz seçilmiş bir teklif yok.');
    }

    $pdf = Pdf::loadView('admin.teklif-talebi.pdf', [
        'talep' => $talep,
        'cevap' => $secilenCevap
    ]);

    return $pdf->download("Teklif-Talebi-{$talep->id}.pdf");
}
    
public function kabulEt($cevapId)
{
    $cevap = TeklifCevap::findOrFail($cevapId);

 
    TeklifCevap::where('teklif_talep_id', $cevap->teklif_talep_id)
        ->update(['kabul_edildi_mi' => false]);


    $cevap->update(['kabul_edildi_mi' => true]);

    return back()->with('success', 'Teklif başarıyla kabul edildi.');
}

public function sendMail($id)
{
    $talep = TeklifTalebi::with('urunler')->findOrFail($id);
    $urunler = $talep->urunler;
    $sonTeslimTarihi = $talep->son_teslim_tarihi;
    $talepId = $talep->id;

    $tedarikciler = Tedarikci::all();

    foreach ($tedarikciler as $tedarikci) {
        Mail::to($tedarikci->eposta)->send(new TalepMail($urunler, $sonTeslimTarihi, $talepId));
    }

    return back()->with('success', 'Tüm tedarikçilere mail gönderildi.');
}

public function mailGonder(Request $request, $id)
{
    $talep = TeklifTalebi::with('urunler')->findOrFail($id);
    $tedarikciler = Tedarikci::whereIn('id', $request->tedarikciler)->get();

    foreach ($tedarikciler as $tedarikci) {
        Mail::to($tedarikci->eposta)->send(new TalepMail($talep->urunler, $talep->son_teslim_tarihi, $talep->id));
    }

    return redirect()->back()->with('success', 'Mail gönderildi.');
}

public function mailSelective(Request $request, $id)
{
    $talep = TeklifTalebi::with('urunler')->findOrFail($id);
    $selectedIds = $request->input('tedarikciler', []);
    $tedarikciler = Tedarikci::whereIn('id', $selectedIds)->get();

    foreach ($tedarikciler as $tedarikci) {
        Mail::to($tedarikci->eposta)->send(new TalepMail(
            $talep->urunler,
            $talep->son_teslim_tarihi,
            $talep->id
        ));
    }

    return redirect()->back()->with('success', 'Seçilen tedarikçilere mail gönderildi.');
}
}
