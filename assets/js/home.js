document.addEventListener('DOMContentLoaded', async () => {
  const featuredGrid = document.getElementById('featuredGrid');
  const popularGrid = document.getElementById('popularGrid');

  featuredGrid.innerHTML = shiftSkeletonCards(4);
  popularGrid.innerHTML = shiftSkeletonCards(4);

  try {
    await shiftLoadFavorites();
    const data = await shiftGetCatalog();
    const products = data.products || [];

    const featured = products.filter(p => p.is_featured).slice(0, 4);
    const popular = [...products]
      .sort((a, b) => Number(b.reviews_count) - Number(a.reviews_count))
      .slice(0, 4);

    featuredGrid.innerHTML = featured.length
      ? featured.map(shiftProductCard).join('')
      : '<div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Ainda não existem serviços em destaque.</div>';

    popularGrid.innerHTML = popular.length
      ? popular.map(shiftProductCard).join('')
      : '<div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Ainda não existem serviços disponíveis.</div>';

    shiftBindProductActions(featuredGrid);
    shiftBindProductActions(popularGrid);
  } catch (error) {
    featuredGrid.innerHTML = `<div class="col-span-full rounded-xl bg-red-50 p-5 text-sm text-red-700">${shiftEscape(error.message)}</div>`;
    popularGrid.innerHTML = '';
  }
});
