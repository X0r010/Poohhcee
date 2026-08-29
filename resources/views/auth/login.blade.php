<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Poohhcee</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    boxShadow: {
                        '2xs': '0 1px 2px 0 rgba(0, 0, 0, 0.04)',
                        'xs': '0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08)',
                    }
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-white min-h-screen flex items-center justify-center px-4 text-zinc-900 antialiased text-xs">

    <div class="w-full max-w-[360px] space-y-6">

        <!-- Brand Header (White Badge Default) -->
        <div class="flex items-center gap-2.5 justify-center">
            <div class="w-8 h-8 rounded-lg bg-white border border-zinc-200 flex items-center justify-center font-bold text-zinc-900 text-sm shadow-2xs">P</div>
            <span class="font-bold text-zinc-900 text-base tracking-tight">Poohhcee</span>
        </div>

        <!-- Login Card -->
        <div class="bg-white border border-zinc-200/80 rounded-xl p-6 shadow-xs">
            <div class="mb-5">
                <h1 class="text-base font-bold text-zinc-900 tracking-tight">Sign in</h1>
                <p class="text-xs text-zinc-500 mt-0.5">Welcome back — enter your details below.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-rose-50 text-rose-700 border border-rose-200/80 text-xs px-3.5 py-2.5 font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="name@example.com"
                           class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1.5">Password</label>
                    <input type="password" name="password" required
                           placeholder="••••••••"
                           class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 transition-colors">
                </div>

                <div class="flex items-center justify-between py-0.5">
                    <label class="flex items-center gap-2 text-xs text-zinc-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 accent-zinc-900">
                        <span>Remember me</span>
                    </label>
                </div>

                <!-- Primary Sign In Button -->
                <button type="submit"
                        class="w-full bg-zinc-900 text-white hover:bg-zinc-800 text-xs font-semibold h-9 rounded-lg shadow-xs transition-colors">
                    Sign In
                </button>
            </form>
        </div>

        <!-- Footer Tag -->
        <p class="text-center text-[11px] text-zinc-400 font-medium">
            Studio v1.0 &bull; Poohhcee
        </p>
    </div>

</body>
</html>