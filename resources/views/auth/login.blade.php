<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Logistics System</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-sky-500 shadow-lg shadow-sky-500/30 text-white text-2xl font-bold mb-3">
                <i class="fas fa-cubes"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Logistics System</h1>
            <p class="text-sm text-slate-400 mt-1">Sign in with your organizational account</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-6 sm:p-8 shadow-xl backdrop-blur-sm">
            @if(session('success'))
                <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-2.5 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-2.5 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="login" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Username, Email, or Employee ID
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fas fa-user text-sm"></i>
                        </span>
                        <input type="text" name="login" id="login" required autofocus
                            value="{{ old('login', 'joshua') }}"
                            placeholder="e.g. joshua or asierr389@gmail.com"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" name="password" id="password" required
                            value="joshua"
                            placeholder="Enter your password"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-sky-500 focus:ring-sky-500">
                        <span>Remember me</span>
                    </label>
                    <span class="text-sky-400 hover:text-sky-300 cursor-pointer">Self-service active</span>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-sky-500 hover:bg-sky-600 text-white font-semibold text-sm rounded-lg shadow-md shadow-sky-500/25 transition duration-150 flex items-center justify-center gap-2">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>

        <!-- Quick Demo Credentials Selector -->
        <div class="mt-6 bg-slate-800/50 border border-slate-700/50 rounded-xl p-4">
            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <i class="fas fa-key text-sky-400"></i>
                <span>Quick Fill Demo Accounts</span>
            </p>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button type="button" onclick="fillCreds('joshua', 'joshua')" class="px-2.5 py-1.5 bg-slate-700/60 hover:bg-slate-700 text-slate-200 rounded text-left transition">
                    <span class="font-bold text-sky-400 block">Admin</span>
                    <span class="text-[11px] text-slate-400">joshua / joshua</span>
                </button>
                <button type="button" onclick="fillCreds('jay jay', 'jayjay123')" class="px-2.5 py-1.5 bg-slate-700/60 hover:bg-slate-700 text-slate-200 rounded text-left transition">
                    <span class="font-bold text-sky-400 block">Fleet Manager</span>
                    <span class="text-[11px] text-slate-400">jay jay / jayjay123</span>
                </button>
                <button type="button" onclick="fillCreds('john john', 'johnjohn123')" class="px-2.5 py-1.5 bg-slate-700/60 hover:bg-slate-700 text-slate-200 rounded text-left transition">
                    <span class="font-bold text-sky-400 block">Driver</span>
                    <span class="text-[11px] text-slate-400">john john / johnjohn123</span>
                </button>
                <button type="button" onclick="fillCreds('john doe', 'johndoe123')" class="px-2.5 py-1.5 bg-slate-700/60 hover:bg-slate-700 text-slate-200 rounded text-left transition">
                    <span class="font-bold text-sky-400 block">Mechanic</span>
                    <span class="text-[11px] text-slate-400">john doe / johndoe123</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function fillCreds(login, pass) {
            document.getElementById('login').value = login;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
