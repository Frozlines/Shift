document.addEventListener('DOMContentLoaded', async () => {
  const params = new URLSearchParams(location.search);
  const initialSearch = params.get('search') || '';
  const initialCategory = params.get('category') || '';

  const searchInput = document.getElementById('shopSearch');
  const categorySelect = document.getElementById('categorySelect');
  const sortSelect = document.getElementById('sortSelect');
  const grid = document.getElementById('shopGrid');
  const count = document.getElementById('resultCount');
  const empty = document.getElementById('emptyState');

  searchInput.value = initialSearch;
  grid.innerHTML = shiftSkeletonCards(6);

  try {
    await shiftLoadFavorites();
    const data = await shiftGetCatalog();
    const allProducts = data.products || [];

    categorySelect.innerHTML = `<option value="">Todas as categorias</option>` +
      (data.categories || []).map(category =>
        `<option value="${shiftEscape(category.slug)}">${shiftEscape(category.name)}</option>`
      ).join('');

    categorySelect.value = initialCategory;

    const syncUrl = () => {
      const url = new URL(location.href);
      const query = searchInput.value.trim();
      const category = categorySelect.value;

      query ? url.searchParams.set('search', query) : url.searchParams.delete('search');
      category ? url.searchParams.set('category', category) : url.searchParams.delete('category');
      history.replaceState({}, '', url);
    };

    function render() {
      const query = searchInput.value.trim().toLowerCase();
      const category = categorySelect.value;
      const sort = sortSelect.value;

      let products = allProducts.filter(product => {
        const matchesSearch = !query ||
          String(product.name).toLowerCase().includes(query) ||
          String(product.short_description).toLowerCase().includes(query) ||
          String(product.category).toLowerCase().includes(query);

        const matchesCategory = !category || product.category_slug === category;
        return matchesSearch && matchesCategory;
      });

      if (sort === 'price-asc') products.sort((a, b) => Number(a.price) - Number(b.price));
      if (sort === 'price-desc') products.sort((a, b) => Number(b.price) - Number(a.price));
      if (sort === 'rating') products.sort((a, b) => Number(b.rating) - Number(a.rating));
      if (sort === 'featured') products.sort((a, b) => Number(b.is_featured) - Number(a.is_featured));

      grid.innerHTML = products.map(shiftProductCard).join('');
      count.textContent = `${products.length} serviço${products.length === 1 ? '' : 's'}`;
      empty.classList.toggle('hidden', products.length !== 0);
      syncUrl();
      shiftBindProductActions(grid);
    }

    let searchTimer = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(render, 100);
    });
    categorySelect.addEventListener('change', render);
    sortSelect.addEventListener('change', render);

    document.getElementById('clearFilters')?.addEventListener('click', () => {
      searchInput.value = '';
      categorySelect.value = '';
      sortSelect.value = 'featured';
      render();
      searchInput.focus();
      shiftToast('Pesquisa e categoria foram repostas.', 'info', { title: 'Filtros limpos' });
    });

    render();
  } catch (error) {
    grid.innerHTML = `<div class="col-span-full rounded-xl bg-red-50 p-5 text-sm text-red-700">${shiftEscape(error.message)}</div>`;
  }
});
