<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        // Get all users for mentor and network leader dropdowns
        $mentors = User::where('is_active', true)
            ->where(function($query) {
                $query->whereHas('mentorships')
                      ->orWhere('is_primary_leader', true);
            })
            ->get();

        $networkLeaders = User::where('is_active', true)
            ->where(function($query) {
                $query->whereHas('mentorships')
                      ->orWhere('is_primary_leader', true);
            })
            ->get();

        return view('registration', compact('mentors', 'networkLeaders'));
    }

    /**
     * Handle registration form submission.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'contact' => 'nullable|string|max:255',
            'mentor_id' => 'nullable|exists:users,id',
            'network_leader_id' => 'nullable|exists:users,id',
            'gender' => 'nullable|in:male,female',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create the user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => trim($request->first_name . ' ' . $request->last_name), // Keep for backward compatibility
            'email' => $request->email,
            'phone' => $request->contact,
            'network_leader_id' => $request->network_leader_id,
            'gender' => $request->gender,
            'password' => Hash::make(Str::random(16)), // Generate random password
            'is_active' => false, // User needs to be activated by admin
        ]);

        // If mentor is set, create discipleship relationship
        if ($request->mentor_id) {
            \App\Models\Discipleship::create([
                'mentor_id' => $request->mentor_id,
                'disciple_id' => $user->id,
                'started_at' => now(),
                'status' => 'active',
            ]);
        }
        // If network leader is set and no mentor, create discipleship relationship
        elseif ($request->network_leader_id) {
            \App\Models\Discipleship::create([
                'mentor_id' => $request->network_leader_id,
                'disciple_id' => $user->id,
                'started_at' => now(),
                'status' => 'active',
            ]);
        }

        return redirect()->route('registration.success')
            ->with('success', 'Registration successful! Your account is pending activation.');
    }

    /**
     * Show registration success page.
     */
    public function success()
    {
        return view('registration-success');
    }
}

