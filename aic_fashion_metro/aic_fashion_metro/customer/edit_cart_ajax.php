<?php
session_start();
include "../config/database.php";

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success' => false, 'message' => 'Harap login terlebih dahulu']);
    exit;
}

$response = ['success' => false, 'message' => ''];

// UPDATE QUANTITY via AJAX (dari tombol + -)
if(isset($_POST['update_qty']) && isset($_POST['id']) && isset($_POST['action'])){
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    
    // Ambil qty saat ini
    $stmt = $conn->prepare("SELECT qty FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $new_qty = $row['qty'];
        
        if($action == 'plus'){
            $new_qty++;
        } elseif($action == 'minus' && $new_qty > 1){
            $new_qty--;
        }
        
        // Update qty
        $update_stmt = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("iii", $new_qty, $id, $user_id);
        
        if($update_stmt->execute()){
            // Hitung subtotal baru
            $price_stmt = $conn->prepare("
                SELECT p.harga 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.id = ?
            ");
            $price_stmt->bind_param("i", $id);
            $price_stmt->execute();
            $price_result = $price_stmt->get_result();
            $price_data = $price_result->fetch_assoc();
            
            $subtotal = $price_data['harga'] * $new_qty;
            
            $response['success'] = true;
            $response['qty'] = $new_qty;
            $response['subtotal'] = $subtotal;
        } else {
            $response['message'] = 'Gagal mengupdate quantity';
        }
    } else {
        $response['message'] = 'Data tidak ditemukan';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// GET DATA CART (untuk ditampilkan di modal edit)
if(isset($_GET['get_cart']) && isset($_GET['id'])){
    $id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT c.*, p.nama_produk, p.harga, p.gambar 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $cart = $result->fetch_assoc();
        $response['success'] = true;
        $response['data'] = $cart;
    } else {
        $response['message'] = 'Data tidak ditemukan';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// UPDATE DATA CART (simpan dari modal edit)
if(isset($_POST['update_cart']) && isset($_POST['id'])){
    $id = (int)$_POST['id'];
    $qty = (int)$_POST['qty'];
    $size = isset($_POST['size']) ? $_POST['size'] : '';
    $user_id = $_SESSION['user_id'];
    
    // Validasi qty minimal 1
    if($qty < 1){
        $response['message'] = 'Quantity minimal 1';
        echo json_encode($response);
        exit;
    }
    
    // Update ke database
    $stmt = $conn->prepare("UPDATE cart SET qty = ?, size = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("isii", $qty, $size, $id, $user_id);
    
    if($stmt->execute()){
        // Ambil harga produk untuk menghitung subtotal baru
        $price_stmt = $conn->prepare("
            SELECT p.harga FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ?
        ");
        $price_stmt->bind_param("i", $id);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        $price_data = $price_result->fetch_assoc();
        
        $response['success'] = true;
        $response['message'] = 'Berhasil diupdate';
        $response['new_subtotal'] = $price_data['harga'] * $qty;
        $response['new_qty'] = $qty;
        $response['new_size'] = $size;
    } else {
        $response['message'] = 'Gagal mengupdate data';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Jika tidak ada action yang cocok
$response['message'] = 'Permintaan tidak valid';
header('Content-Type: application/json');
echo json_encode($response);
?>