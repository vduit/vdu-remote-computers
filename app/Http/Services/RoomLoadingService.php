<?php


namespace App\Http\Services;


use App\Models\RdpIS\Computers;
use App\Models\RdpIS\Reservations;
use App\Models\RdpIS\RoomComputers;
use App\Models\RdpIS\Rooms;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class RoomLoadingService
{
    /**
     * @return Rooms[]|Collection
     */
    public function getRoomList()
    {
        return Rooms::orderBy('room_name')->get();
    }

    /**
     * @param $ckods
     * @param $computerId
     * @return RedirectResponse
     */
    public function reserveComputer($ckods, $computerId): RedirectResponse
    {
        if (empty($ckods) || empty($computerId)) {
            return redirect()->back()->with('alert-warning', 'Įvyko klaida rezervuojant kambarį, perkraukite puslapį ir mėginkite dar kartą.');
        }

        if ($this->getIfUserHasActiveReservations($ckods)) {
            return redirect()->back()->with('alert-warning', 'Jūs jau turite užrezervuotą kompiuterį.');
        }

        if ($this->getIsComputerReserved($computerId)) {
            return redirect()->back()->with('alert-warning', 'Kompiuteris šiuo metu užrezervuotas.');
        }

        if ($this->isComputerLecturers($computerId) && empty(auth()->user()->ez_lecturer_id)) {
            return redirect()->back()->with('alert-warning', 'Dėstytojų kompiuterius gali rezervuoti tik dėstytojai :)');
        }

        $reservation = new Reservations();

        $reservation->computer_id = $computerId;
        $reservation->ckods = $ckods;
        $reservation->reservation_start_date = date('Y-m-d H:i:s');
        $reservation->save();

        $fullRdpUrl = env('RDP_FILE_URL_ROOT').'/'.$this->getComputerRdpFileUrl($computerId);

        Session::flash('download_url', $fullRdpUrl); // Since we cant do two requests we flash the download url to a session and the once redirected we use the meta tag to download the file
        return redirect()->back()->with('alert-success', 'Sėkmingai užrezervavote kompiuterį.');
    }

    /**
     * @param $ckods
     * @param $computerId
     * @return RedirectResponse
     */
    public function cancelComputerReservation($ckods, $computerId): RedirectResponse
    {
        $r = Reservations::where(['ckods' => $ckods, 'computer_id' => $computerId])->whereNull('is_active')->first();

        if (empty($r)) {
            return redirect()->back()->with('alert-warning', 'Nerasta reservacija atšaukimui.');
        }

        $r->is_active = 0;
        $r->reservation_end_date = date('Y-m-d H:i:s');
        $r->save();

        return redirect()->back()->with('alert-success', 'Sėkmingai atšaukėte kompiuterio rezervaciją.');
    }

    /**
     * @param $computerId
     * @return bool
     */
    public function getIsComputerReserved($computerId): bool
    {
        return Reservations::where('computer_id', $computerId)->whereNull('is_active')->count() > 0;
    }

    /**
     * @param $ckods
     * @return bool
     */
    public function getIfUserHasActiveReservations($ckods): bool
    {
        return Reservations::where('ckods', $ckods)->whereNull('is_active')->count() > 0;
    }

    /**
     * @param $computerId
     * @return mixed
     */
    public function getComputerRdpFileUrl($computerId)
    {
        return Computers::where('id', $computerId)->first()->rdp_file_url;
    }

    /**
     * @param $computerId
     * @return string
     */
    public function getFullComputerRdpFileUrl($computerId): string
    {
        return env('RDP_FILE_URL_ROOT').'/'.$this->getComputerRdpFileUrl($computerId);
    }

    /**
     * @param $computerId
     * @return bool
     */
    public function isComputerLecturers($computerId): bool
    {
        return Computers::where('id', $computerId)->first()->is_computer_lecturers === '1';
    }

    /**
     * @param null $ckods
     * @return string
     */
    public function getUsersActiveReservationPc($ckods = null): string
    {
        if(is_null($ckods)) {
            $ckods = $this->getActiveUserCkods();
        }
        return Reservations::where('ckods', $ckods)->whereNull('is_active')->first()->computer_id ?? '';
    }

    /**
     * @return mixed
     */
    public function getActiveUserCkods()
    {
        return auth()->user()->cilveks_ckods;
    }

    /**
     * @param $computer_id
     * @return mixed
     */
    public function getUsersActiveReservationPcName($computer_id)
    {
        return Computers::where('id', $computer_id)->first()->pc_name;
    }

    /**
     * @param $computer_id
     * @return HigherOrderBuilderProxy|mixed
     */
    public function getUsersActiveReservationRoomName($computer_id)
    {
        return RoomComputers::with('rooms')->where('computer_id', $computer_id)->first()->rooms->room_name;
    }
}
