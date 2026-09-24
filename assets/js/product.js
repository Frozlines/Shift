document.addEventListener('DOMContentLoaded', async () => {
  const addButton = document.getElementById('addProductToCart');
  const quantity = document.getElementById('quantity');
  const stock = Number(addButton?.dataset.stock || 1);
  const productId = Number(addButton?.dataset.productId || 0);

  const normalizeQuantity = () => {
    const value = Math.max(1, Math.min(stock, Number(quantity.value) || 1));
    quantity.value = value;
    return value;
  };

  document.getElementById('decreaseQty')?.addEventListener('click', () => {
    quantity.value = Math.max(1, normalizeQuantity() - 1);
  });

  document.getElementById('increaseQty')?.addEventListener('click', () => {
    quantity.value = Math.min(stock, normalizeQuantity() + 1);
  });

  quantity?.addEventListener('change', normalizeQuantity);

  addButton?.addEventListener('click', () => {
    shiftAddToCart(productId, normalizeQuantity(), addButton);
  });

  document.querySelectorAll('[data-wishlist]').forEach(button => {
    button.addEventListener('click', () => shiftToggleFavorite(Number(button.dataset.wishlist), false, button));
  });

  const relatedGrid = document.getElementById('relatedGrid');
  if (relatedGrid) relatedGrid.innerHTML = shiftSkeletonCards(4);

  try {
    await shiftLoadFavorites();
    const data = await shiftGetCatalog();
    const currentId = Number(relatedGrid?.dataset.currentId || 0);
    const related = (data.products || []).filter(p => Number(p.id) !== currentId).slice(0, 4);

    if (relatedGrid) {
      relatedGrid.innerHTML = related.map(shiftProductCard).join('');
      shiftBindProductActions(relatedGrid);
    }
    shiftSyncFavoriteButtons();
  } catch {
    if (relatedGrid) relatedGrid.innerHTML = '';
  }
});
