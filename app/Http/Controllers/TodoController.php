<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index()
    {
        $todos = Todo::latest()->get();

        return view('todos.index', compact('todos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        Todo::create($validated);

        return redirect()->route('todos.index');
    }

    public function update(Request $request, Todo $todo): RedirectResponse
    {
        $todo->update([
            'completed' => $request->boolean('completed'),
        ]);

        return redirect()->route('todos.index');
    }

    public function destroy(Todo $todo): RedirectResponse
    {
        $todo->delete();

        return redirect()->route('todos.index');
    }
}
