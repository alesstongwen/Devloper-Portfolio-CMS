<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('techStacks')->where('user_id', auth()->id())->get();
        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $techStacks = \App\Models\TechStack::all();
        return view('projects.create', compact('techStacks'));
    }

    /**
     * Store a newly created resource in storage.
     */

     public function store(Request $request)
     {
         \Log::debug('Validation input:', $request->all());
         
         $validated = $request->validate([
             'title' => 'required|string|max:255',
             'description' => 'required',
             'github_url' => 'nullable|url',
             'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', 
             'tech_stack_ids' => 'array'
         ]);
     
         if ($request->hasFile('image')) {
             $file = $request->file('image');
         
             if ($file->isValid()) {
                 $path = $file->store('project_images', 'public');
                 \Log::debug('✅ Image stored at: ' . $path);
                 $validated['image'] = $path;
             } else {
                 \Log::debug('❌ Uploaded file is not valid.', [
                     'errorCode' => $file->getError(),
                     'originalName' => $file->getClientOriginalName()
                 ]);
             }
         } else {
             \Log::debug('❌ No image file present in request.');
         }
         
         $project = new Project($validated);
         $project->user_id = auth()->id(); // associate project with logged-in user
         $project->save();
     
         $project->techStacks()->sync($request->input('tech_stack_ids', []));
     
         return redirect()->route('projects.index')->with('success', 'Project created!');
     }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $techStacks = \App\Models\TechStack::all();
        return view('projects.edit', compact('project', 'techStacks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'github_url' => 'nullable|url',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', 
            'tech_stack_ids' => 'array'
        ]);
    
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('project_images', 'public');
        } else {
            unset($validated['image']); // ← prevent overwriting existing image
        }
    
        $project->update($validated);
        $project->techStacks()->sync($request->input('tech_stack_ids', []));
    
        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        //
    }
}
