<!DOCTYPE html>
<html>
<head>
    <title>Thanh Toán</title>
</head>
<body>
<form action="/payment" method="POST">
    @csrf
    <input type="text" name="order_id" placeholder="Order ID" required>
    <input type="number" name="amount" placeholder="Amount" required>
    <input type="text" name="currency" placeholder="Currency" value="VND" required>
    <input type="text" name="description" placeholder="Description" required>
    <button type="submit">Thanh Toán</button>
</form>
</body>
</html>
