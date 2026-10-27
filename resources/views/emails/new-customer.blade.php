<h1>Novo registo no Dream Gym</h1>
<p>Nome: {{ $customer->name }}</p><p>Email: {{ $customer->email }}</p><p>Telefone: {{ $customer->phone ?: '—' }}</p>
<p>Registado em {{ $customer->created_at->format('d/m/Y H:i:s') }}.</p>
