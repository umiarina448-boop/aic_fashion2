<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* HAPUS ITEM */
if(isset($_GET['hapus'])){
    $id = (int)$_GET['hapus'];
    
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    
    header("Location: cart.php");
    exit;
}

/* DATA CART */
$stmt = $conn->prepare("
    SELECT c.*, p.nama_produk, p.harga, p.gambar, p.stok
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - AIC Fashion Metro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            padding-bottom: 100px;
        }

        /* BACK BUTTON */
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #EE6C4D;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0,0,0,.1);
            transition: .2s;
            z-index: 999;
        }

        .back-btn:hover {
            background: #EE6C4D;
            color: white;
            transform: translateX(-3px);
        }

        /* CONTAINER */
        .container {
            max-width: 1000px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        .container h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* EMPTY CART */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .empty-cart i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-cart p {
            color: #999;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .shop-link {
            display: inline-block;
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 40px;
            font-weight: 600;
            transition: 0.2s;
        }

        /* CARD ITEM */
        .card {
            background: white;
            padding: 20px;
            margin-bottom: 12px;
            border-radius: 20px;
            display: flex;
            gap: 18px;
            align-items: center;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
            transition: 0.2s;
            border: 1px solid #f0f0f0;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .card input[type="checkbox"] {
            transform: scale(1.2);
            accent-color: #EE6C4D;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            background: #f5f5f5;
        }

        .info {
            flex: 1;
        }

        .info h4 {
            font-size: 16px;
            margin-bottom: 6px;
            color: #333;
        }

        .info p {
            font-size: 13px;
            color: #777;
            margin-bottom: 8px;
        }

        .price {
            color: #EE6C4D;
            font-weight: 700;
            font-size: 16px;
            margin-top: 8px;
        }

        /* QUANTITY */
        .qty {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .qty button {
            width: 30px;
            height: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            background: white;
            font-weight: bold;
            font-size: 16px;
            transition: 0.2s;
        }

        .qty button:hover {
            background: #EE6C4D;
            color: white;
            border-color: #EE6C4D;
        }

        .qty span {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        /* ACTION BUTTONS */
        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .icon-btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            transition: 0.2s;
            cursor: pointer;
            border: none;
        }

        .icon-btn:hover {
            transform: scale(1.05);
        }

        .delete {
            background: #ff4d4d;
        }

        .delete:hover {
            background: #e60000;
        }

        .edit {
            background: #3498db;
        }

        .edit:hover {
            background: #2980b9;
        }

        /* FOOTER CHECKOUT */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 16px 30px;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
            flex-wrap: wrap;
            gap: 15px;
        }

        .total {
            font-weight: 700;
            font-size: 18px;
            color: #333;
        }

        .total span {
            color: #EE6C4D;
            font-size: 24px;
            margin-left: 8px;
        }

        .checkout-btn {
            padding: 12px 28px;
            background: #EE6C4D;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkout-btn:hover {
            background: #d95a3c;
            transform: translateY(-2px);
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* ========== MODAL POPUP ========== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 450px;
            border-radius: 24px;
            animation: modalSlideIn 0.3s ease;
            overflow: hidden;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: #EE6C4D;
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: 0.2s;
        }

        .close-modal:hover {
            transform: scale(1.1);
        }

        .modal-body {
            padding: 25px;
        }

        .product-preview {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .product-preview img {
            width: 70px;
            height: 70px;
        }

        .product-info h4 {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .product-info .modal-price {
            color: #EE6C4D;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: #EE6C4D;
        }

        .modal-footer {
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            gap: 12px;
        }

        .modal-footer button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #666;
        }

        .btn-cancel:hover {
            background: #ccc;
        }

        .btn-save {
            background: #EE6C4D;
            color: white;
        }

        .btn-save:hover {
            background: #d95a3c;
        }

        /* SELECT ALL */
        .select-all {
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @media (max-width: 700px) {
            .card {
                flex-wrap: wrap;
            }
            .actions {
                flex-direction: row;
                width: 100%;
                justify-content: flex-end;
            }
            .footer {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<a href="javascript:history.back()" class="back-btn">
    <i class="fa-solid fa-chevron-left"></i>
</a>

<div class="container">
    <h2>
        <i class="fa-solid fa-cart-shopping" style="color: #EE6C4D;"></i> 
        Keranjang Belanja
    </h2>

    <?php 
    $total_item = $data->num_rows;
    
    if($total_item == 0){ ?>
        <div class="empty-cart">
            <i class="fa-solid fa-basket-shopping"></i>
            <p>Keranjang belanja masih kosong</p>
            <a href="dashboard.php" class="shop-link">
                <i class="fa-solid fa-shop"></i> Yuk Belanja Sekarang
            </a>
        </div>
    <?php } else { ?>
        
        <div class="select-all">
            <input type="checkbox" id="selectAllCheckbox">
            <label for="selectAllCheckbox">Pilih Semua</label>
        </div>

        <form id="checkoutForm" method="POST" action="checkout.php">
            <div id="cartItems">
                <?php while($row = $data->fetch_assoc()){ 
                    $subtotal = $row['harga'] * $row['qty'];
                ?>
                    <div class="card" data-id="<?= $row['id']; ?>">
                        <input type="checkbox" name="cart_ids[]" value="<?= $row['id']; ?>" 
                               class="item-checkbox" data-price="<?= $subtotal; ?>">

                        <img src="../uploads/<?php echo htmlspecialchars($row['gambar']); ?>" 
                             alt="<?php echo htmlspecialchars($row['nama_produk']); ?>"
                             onerror="this.src='https://placehold.co/200x200?text=No+Image'">

                        <div class="info">
                            <h4><?php echo htmlspecialchars($row['nama_produk']); ?></h4>
                            <p>Size: <span class="size-text-<?= $row['id']; ?>"><?php echo htmlspecialchars($row['size'] ?? 'Free'); ?></span></p>
                            
                            <div class="qty">
                                <button type="button" class="qty-btn" data-id="<?= $row['id']; ?>" data-action="minus">-</button>
                                <span id="qty-<?= $row['id']; ?>"><?= $row['qty']; ?></span>
                                <button type="button" class="qty-btn" data-id="<?= $row['id']; ?>" data-action="plus">+</button>
                            </div>

                            <div class="price" id="price-<?= $row['id']; ?>">
                                Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                            </div>
                        </div>

                        <div class="actions">
                            <button type="button" class="icon-btn edit edit-cart-btn" data-id="<?= $row['id']; ?>">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <a href="?hapus=<?= $row['id']; ?>" class="icon-btn delete"
                               onclick="return confirm('Yakin ingin menghapus item ini?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="footer">
                <div class="total">
                    Total:
                    <span id="totalPrice">Rp 0</span>
                </div>
                <button type="submit" id="checkoutButton" class="checkout-btn" disabled>
                    <i class="fa-solid fa-credit-card"></i> Checkout Sekarang
                </button>
            </div>
        </form>
    <?php } ?>
</div>

<!-- MODAL POPUP EDIT CART -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Item</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="product-preview" id="modalProductPreview">
                <img id="modalProductImage" src="" alt="">
                <div class="product-info">
                    <h4 id="modalProductName"></h4>
                    <div class="modal-price" id="modalProductPrice"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Ukuran (Size)</label>
                <select id="editSize" class="size-select">
                    <option value="S">S (Small)</option>
                    <option value="M">M (Medium)</option>
                    <option value="L">L (Large)</option>
                    <option value="XL">XL (Extra Large)</option>
                    <option value="XXL">XXL (Double Extra Large)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Jumlah (Quantity)</label>
                <input type="number" id="editQty" min="1" value="1">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Batal</button>
            <button class="btn-save" onclick="saveEdit()">Simpan Perubahan</button>
        </div>
    </div>
</div>

<script>
let currentEditId = null;

// UPDATE QUANTITY via AJAX
document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        const action = this.dataset.action;
        
        try {
            const formData = new URLSearchParams();
            formData.append('update_cart', '1');
            formData.append('id', id);
            formData.append('action', action);
            
            const response = await fetch('edit_cart_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });
            
            const result = await response.json();
            
            if(result.success) {
                document.getElementById(`qty-${id}`).innerText = result.new_qty;
                const priceElement = document.getElementById(`price-${id}`);
                priceElement.innerHTML = `Rp ${result.new_subtotal.toLocaleString()}`;
                
                const checkbox = document.querySelector(`input[value="${id}"]`);
                if(checkbox) {
                    checkbox.setAttribute('data-price', result.new_subtotal);
                    if(checkbox.checked) {
                        updateTotal();
                    }
                }
            } else {
                alert('Gagal mengupdate quantity');
            }
        } catch(error) {
            console.error('Error:', error);
        }
    });
});

// OPEN MODAL EDIT
document.querySelectorAll('.edit-cart-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        currentEditId = id;
        
        try {
            const response = await fetch(`edit_cart_ajax.php?get_cart=1&id=${id}`);
            const result = await response.json();
            
            if(result.success) {
                const data = result.data;
                
                // Isi modal dengan data produk
                document.getElementById('modalProductName').innerText = data.nama_produk;
                document.getElementById('modalProductImage').src = `../uploads/${data.gambar}`;
                document.getElementById('modalProductPrice').innerHTML = `Rp ${parseInt(data.harga).toLocaleString()}`;
                document.getElementById('editSize').value = data.size || 'M';
                document.getElementById('editQty').value = data.qty;
                
                // Tampilkan modal
                document.getElementById('editModal').classList.add('show');
            } else {
                alert('Gagal mengambil data');
            }
        } catch(error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        }
    });
});

// SAVE EDIT
async function saveEdit() {
    if(!currentEditId) return;
    
    const newSize = document.getElementById('editSize').value;
    const newQty = document.getElementById('editQty').value;
    
    try {
        const formData = new URLSearchParams();
        formData.append('update_cart', '1');
        formData.append('id', currentEditId);
        formData.append('qty', newQty);
        formData.append('size', newSize);
        
        const response = await fetch('edit_cart_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });
        
        const result = await response.json();
        
        if(result.success) {
            // Update tampilan di halaman
            document.getElementById(`qty-${currentEditId}`).innerText = result.new_qty;
            document.querySelector(`.size-text-${currentEditId}`).innerText = result.new_size;
            
            const priceElement = document.getElementById(`price-${currentEditId}`);
            priceElement.innerHTML = `Rp ${result.new_subtotal.toLocaleString()}`;
            
            const checkbox = document.querySelector(`input[value="${currentEditId}"]`);
            if(checkbox) {
                checkbox.setAttribute('data-price', result.new_subtotal);
                if(checkbox.checked) {
                    updateTotal();
                }
            }
            
            closeModal();
            alert('Berhasil mengupdate item!');
        } else {
            alert(result.message || 'Gagal mengupdate');
        }
    } catch(error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    }
}

// CLOSE MODAL
function closeModal() {
    document.getElementById('editModal').classList.remove('show');
    currentEditId = null;
}

// TOTAL CHECKBOX
let checkboxes = document.querySelectorAll(".item-checkbox");
let totalEl = document.getElementById("totalPrice");
let checkoutBtn = document.getElementById("checkoutButton");
let selectAllCheckbox = document.getElementById("selectAllCheckbox");

function updateTotal() {
    let total = 0;
    let anyChecked = false;
    
    checkboxes.forEach(cb => {
        if(cb.checked) {
            total += parseInt(cb.getAttribute("data-price") || 0);
            anyChecked = true;
        }
    });
    
    totalEl.innerText = "Rp " + total.toLocaleString();
    checkoutBtn.disabled = !anyChecked;
    
    if(selectAllCheckbox) {
        const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
    }
}

if(selectAllCheckbox) {
    selectAllCheckbox.addEventListener("change", function() {
        checkboxes.forEach(cb => {
            cb.checked = selectAllCheckbox.checked;
        });
        updateTotal();
    });
}

checkboxes.forEach(cb => {
    cb.addEventListener("change", updateTotal);
});

updateTotal();

// Cegah submit form jika tidak ada yang dipilih
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
    if(!anyChecked) {
        e.preventDefault();
        alert('Pilih minimal 1 produk untuk checkout');
    }
});

// Klik di luar modal untuk menutup
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

</body>
</html>