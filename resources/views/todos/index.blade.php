<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>Todos</title>
</head>
<body>
    <h1>Todos</h1>

    <form method="POST" action="{{ route('todos.store') }}">
        @csrf
        <input type="text" name="title" placeholder="New todo" required>
        <button type="submit">Add</button>
    </form>

    <ul>
        @foreach ($todos as $todo)
            <li>
                <form method="POST" action="{{ route('todos.update', $todo) }}" style="display:inline">
                    @csrf
                    @method('PUT')
                    <input type="checkbox" name="completed" value="1" onchange="this.form.submit()" @checked($todo->completed)>
                    <span @style(['text-decoration: line-through' => $todo->completed])>{{ $todo->title }}</span>
                </form>
                <form method="POST" action="{{ route('todos.destroy', $todo) }}" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Delete</button>
                </form>
            </li>
        @endforeach
    </ul>
</body>
</html>
