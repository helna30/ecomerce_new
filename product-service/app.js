const express = require('express');
const app = express();
const sequelize = require('./database');
const { DataTypes } = require('sequelize');
const cors = require('cors');

// Middleware
app.use(express.json());
app.use(cors());

// Define Model
const Product = sequelize.define("Product", {
  name: {
    type: DataTypes.STRING,
    allowNull: false
  },
  description: {
    type: DataTypes.STRING,
    allowNull: true
  },
  price: {
    type: DataTypes.FLOAT,
    allowNull: false
  }
});

// Init DB
const initDb = async () => {
  try {
    await sequelize.sync({ alter: true });
    console.log("Tabel produk tersinkron dengan database");
  } catch (error) {
    console.log("Error ketika membuat database", error);
  }
};

initDb();

// Standar Response
const successResponse = (res, message, data = null) => {
  res.status(200).json({
    success: true,
    message: message,
    data: data
  });
};

const errorResponse = (res, statusCode, message) => {
  res.status(statusCode).json({
    success: false,
    message: message
  });
};

//
// === ROUTES CRUD ===
//

// CREATE - Tambah produk
app.post('/products', async (req, res) => {
  try {
    const { name, description, price } = req.body;

    if (!name || !price) {
      return errorResponse(res, 400, "Nama dan harga produk harus diisi");
    }

    const newProduct = await Product.create({ name, description, price });
    successResponse(res, "Berhasil menambah produk", newProduct);
  } catch (error) {
    console.log(error);
    errorResponse(res, 500, "Gagal menambah produk");
  }
});

// READ - Ambil semua produk
app.get('/products', async (req, res) => {
  try {
    const products = await Product.findAll();
    successResponse(res, "Berhasil mengambil semua produk", products);
  } catch (error) {
    console.log(error);
    errorResponse(res, 500, "Gagal mengambil data produk");
  }
});

// READ (by ID) - Ambil 1 produk berdasarkan ID
app.get('/products/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const product = await Product.findByPk(id);

    if (!product) {
      return errorResponse(res, 404, "Produk tidak ditemukan");
    }

    successResponse(res, "Berhasil mengambil produk", product);
  } catch (error) {
    console.log(error);
    errorResponse(res, 500, "Gagal mengambil produk");
  }
});

// UPDATE - Ubah data produk
app.put('/products/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, description, price } = req.body;

    const product = await Product.findByPk(id);
    if (!product) {
      return errorResponse(res, 404, "Produk tidak ditemukan");
    }

    await product.update({ name, description, price });
    successResponse(res, "Produk berhasil diperbarui", product);
  } catch (error) {
    console.log(error);
    errorResponse(res, 500, "Gagal memperbarui produk");
  }
});

// DELETE - Hapus produk
app.delete('/products/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const product = await Product.findByPk(id);

    if (!product) {
      return errorResponse(res, 404, "Produk tidak ditemukan");
    }

    await product.destroy();
    successResponse(res, "Produk berhasil dihapus");
  } catch (error) {
    console.log(error);
    errorResponse(res, 500, "Gagal menghapus produk");
  }
});

// Jalankan server
app.listen(3000, () => {
  console.log("Server berjalan di port 3000");
});