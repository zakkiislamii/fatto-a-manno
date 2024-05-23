<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Models\User;
use App\Models\Cloth;
use App\Models\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;



class BuyController extends Controller
{
    public function addBuy(Request $request)
    {
        //Validate Request
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'cloth_id' => 'required|exists:cloths,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'payment_status' => 'integer',
            'confirmation_status' => 'integer',
        ]);

        // Check if the user exists

        // if(!$user) {
        //     return response()->json(['message' => 'User not found'], 404);
        // }

        // Check if validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages());
        }

        $user = User::find($request->user_id);
        // Find the cloth
        $cloth = Cloth::find($request->cloth_id);
        $storage = $cloth->storages()->first();

        // // Check if the cloth exists
        //  if(!$cloth) {
        //     return response()->json(['message' => 'Cloth not found'], 404);
        // }

        // Check if the storage exists
        if (!$storage) {
            return redirect()->back()->withErrors('Storage not Found');
        }

        //check if the quantity exceed the storage limit
        if ($storage->quantity_limit < $request->quantity) {
            return redirect()->back()->withErrors('Storage Quantity Exceeded!');
        }

        // // Create the buy record
        // $cloth->users()->attach($user, [
        //     'quantity' => $request->quantity,
        //     'payment_method' => $request->payment_method,
        //     'payment_status' => 0,
        //     'confirmation_status' => 0
        // ]);

        DB::transaction(function () use ($user, $cloth, $storage, $request) {
            $cloth->users()->attach($user, [
                'quantity' => $request->quantity,
                'payment_method' => $request->payment_method,
                'payment_status' => 0,
                'confirmation_status' => 0
            ]);

            // Update the storage quantity
            $storage->quantity_limit -= $request->quantity;
            $storage->save();
        });

        // 
        // $buy = Buy::where('quantity', $request->quantity) 
        // ->where('payment_method', $request->payment_method)
        // ->where( 'payment_status' , $request->payment_status)
        // ->where( 'confirmation_status' , $request->confirmation_status)->first();
        // Retrieve the latest buy record for the user and cloth
        $buy = $cloth->users()->wherePivot('user_id', $user->id)->latest('pivot_created_at')->first();

        // Update the storage quantity
        $storage->quantity_limit -= $request->quantity;
        $storage->save();

        // $buy = Buy::orderBy('id', 'desc')->first();

        //check if the quantity exceed the storage limit
        if ($storage->quantity_limit < 0) {
            return redirect()->back()->withErrors('Storage Quantity Exceeded!');
        }

        $buy = $cloth->users()->latest()->first();


        if ($request->is('api/*')) {
            return response()->json(['buy' => $buy], 201);
        }

        return redirect()->route('Data Pembelian');
    }

    public function editBuy($id, Request $request)
    {
        //Validate Request
        $validator = Validator::make($request->all(), [
            'quantity' => 'integer|min:1',
            'payment_method' => 'string',
            'payment_status' => 'integer',
            'confirmation_status' => 'integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages());
        }

        $buy = Buy::find($id);

        if (!$buy) {
            return redirect()->back()->withErrors('Buy not Found');
        }

        $buy->update($request->all());

        if ($buy) {
            $res = response()->json([
                'buy' => $buy,
            ]);

            if ($request->is('api/*')) {
                return response()->json(['buy' => $buy], 201);
            }

            return redirect()->route('Data Pembelian');
        } else {
            return redirect()->back()->withErrors('Edit Failed');
        }
    }

    public function editPayment($id)
    {

        $buy = Buy::find($id);

        if (!$buy) {
            return response()->json(['message' => 'Buy not found'], 404);
        }

        $buy->update([
            'payment_status' => 1
        ]);

        if ($buy) {
            if (request()->is('api/*')) {
                return response()->json(['message' => "Successfully Confirmed"], 200);
            }
            return redirect()->route('Data Pembelian');
        } else {
            return redirect()->back()->withErrors('Change Failed');
        }
    }

    public function deleteBuy($id)
    {
        $buy = Buy::find($id);

        if (!$buy) {
            return response()->json(['message' => 'Buy not found'], 404);
        }

        if ($buy->delete()) {
            if (request()->is('api/*')) {
                return response()->json(['message' => "Successfully Deleted"], 200);
            }
            return redirect()->route('Data Pembelian');
        } else {
            return redirect()->back()->withErrors('Delete Failed');
        }
    }

    public function getAllBuys()
    {
        $buys = Buy::paginate(10, ['*'], 'buys_page');

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'buys' => $buys,
            ]);
        }

        return view('Buy.data_pembelian', ['title' => 'Data Pembelian'], compact('buys'));
    }

    public function getBuyById($id)
    {
        $buy = Buy::find($id);

        if (!$buy) {
            return response()->json(['message' => 'Buy not found'], 404);
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'buy' => $buy,
            ]);
        }

        return view('Buy.edit_pembelian', ['title' => 'Data Pembelian'], compact('buy'));
    }

    public function getBuybyAttribute($user_id)
    {
        $validator = Validator::make(request()->all(), [
            'payment_method' => 'sometimes',
            'payment_status' => 'sometimes|in:0,1',
            'confirmation_status' => 'sometimes|in:0,1,2',
        ]);

        $payment_method = request('payment_method', null);
        $payment_status = request('payment_status', null);
        $confirmation_status = request('confirmation_status', null);

        // Build query conditions based on provided arguments
        $query = Buy::with('user'); // Eager load the user relationship

        if (!is_null($payment_method)) {
            $query->where('payment_method', $payment_method);
        }

        if (!is_null($payment_status)) {
            $query->where('payment_status', $payment_status);
        }

        if (!is_null($confirmation_status)) {
            $query->where('confirmation_status', $confirmation_status);
        }

        $query->where('user_id', $user_id);

        // Get the results
        $results = $query->get();


        // Paginate the results for clothes
        $perPage = 10;
        $page = request()->get('buys_page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $results->slice($offset, $perPage);
        $buys = new LengthAwarePaginator(
            $paginatedResults,
            $results->count(),
            $perPage,
            $page,
            ['path' => request()->fullUrl(), 'pageName' => 'buys_page']
        );

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'buys' => $buys
            ]);
        }

        return view('Admin.detail_user', ['title' => 'Detail User'], compact('buys'));
    }

    public function getBuybyAttributeCustomer()
    {
        $validator = Validator::make(request()->all(), [
            'payment_method' => 'sometimes',
            'payment_status' => 'sometimes|in:0,1',
            'confirmation_status' => 'sometimes|in:0,1,2',
        ]);

        $payment_method = request('payment_method', null);
        $payment_status = request('payment_status', null);
        $confirmation_status = request('confirmation_status', null);

        // Build query conditions based on provided arguments
        $query = Buy::query();

        if (!is_null($payment_method)) {
            $query->where('payment_method', $payment_method);
        }

        if (!is_null($payment_status)) {
            $query->where('payment_status', $payment_status);
        }

        if (!is_null($confirmation_status)) {
            $query->where('confirmation_status', $confirmation_status);
        }

        $query->where('user_id', auth()->user()->id);

        // Get the results
        $results = $query->get();

        // Paginate the results for clothes
        $perPage = 10;
        $page = request()->get('buys_page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $results->slice($offset, $perPage);
        $buys = new LengthAwarePaginator(
            $paginatedResults,
            $results->count(),
            $perPage,
            $page,
            ['path' => request()->fullUrl(), 'pageName' => 'buys_page']
        );

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'buys' => $buys
            ]);
        }

        return view('Admin.detail_user', ['title' => 'Detail User'], compact('buys'));
    }
}
