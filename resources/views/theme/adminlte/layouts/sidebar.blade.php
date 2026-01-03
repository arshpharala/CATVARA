@php
  use Illuminate\Support\Facades\Route;

  $company = active_company();
  $companyReady = company_selected();

  $isActive = function ($names) {
      foreach ((array) $names as $n) {
          if (request()->routeIs($n)) {
              return true;
          }
      }
      return false;
  };

  // Module visibility flags (only show if at least one route exists)
  $hasSales =
      Route::has('orders.index') ||
      Route::has('quotes.index') ||
      Route::has('invoices.index') ||
      Route::has('credit-notes.index');
  $hasPos = Route::has('company.pos.orders.index') || Route::has('company.pos.returns.index');
  $hasWeb = Route::has('company.web.orders.index');
  $hasAccounting =
      Route::has('payments.index') ||
      Route::has('allocations.index') ||
      Route::has('refunds.index') ||
      Route::has('payment-methods.index');
  $hasInventory =
      Route::has('company.catalog.categories.index') ||
      Route::has('company.catalog.products.index') ||
      Route::has('company.stock-movements.index');
  $hasCustomers = Route::has('customers.index') || Route::has('customer-balances.index');
  $hasReports =
      Route::has('company.reports.sales') ||
      Route::has('company.reports.payments') ||
      Route::has('company.reports.allocations') ||
      Route::has('company.reports.outstanding');
@endphp

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">

  <!-- Brand Logo -->
  <a href="{{ route('dashboard') }}" class="brand-link text-center">
    <span class="brand-text font-weight-light text-center">{{ setting('SITE_NAME', env('APP_NAME')) }}</span>
  </a>

  <div class="sidebar">

    <!-- Sidebar user panel -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="{{ asset('theme/adminlte/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2"
          alt="User Image">
      </div>

      <div class="info">
        <a href="#" class="d-block">{{ auth()->user()->name }}</a>

        <div class="text-muted small" style="line-height: 1.2;">
          @if ($company)
            <i class="fas fa-building mr-1"></i> {{ $company->name }}
            @if (!empty($company->code))
              <span class="text-muted">({{ $company->code }})</span>
            @endif
          @else
            <i class="fas fa-exclamation-triangle mr-1"></i> Company not selected
          @endif
        </div>

        <div class="mt-2">
          @if ($companyReady && can_switch_company())
            <form method="POST" action="{{ route('company.switch.reset') }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-xs btn-outline-light">
                <i class="fas fa-random mr-1"></i> Change
              </button>
            </form>
          @elseif(!$companyReady)
            <a href="{{ route('company.select') }}" class="btn btn-xs btn-outline-light">
              <i class="fas fa-building mr-1"></i> Select Company
            </a>
          @endif
        </div>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-header">MAIN</li>

        {{-- Dashboard --}}
        <li class="nav-item">
          <a href="{{ route('dashboard') }}"
            class="nav-link {{ $isActive(['dashboard', 'company.dashboard']) ? 'active' : '' }}">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        {{-- Company scoped modules - only show if company selected --}}
        @if ($companyReady)

          @if ($hasSales || $hasPos || $hasWeb || $hasAccounting || $hasInventory || $hasCustomers || $hasReports)
            <li class="nav-header">ADMIN RESOURCE</li>
          @endif

          {{-- Sales --}}
          @if ($hasSales)
            <li
              class="nav-item has-treeview {{ $isActive(['orders.*', 'quotes.*', 'invoices.*', 'credit-notes.*']) ? 'menu-open' : '' }}">
              <a href="#"
                class="nav-link {{ $isActive(['orders.*', 'quotes.*', 'invoices.*', 'credit-notes.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-shopping-cart"></i>
                <p>Sales <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">

                @if (Route::has('orders.index'))
                  <li class="nav-item">
                    <a href="{{ company_route('orders.index') }}"
                      class="nav-link {{ $isActive('orders.*') ? 'active' : '' }}">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Orders</p>
                    </a>
                  </li>
                @endif

                @if (Route::has('quotes.index'))
                  <li class="nav-item">
                    <a href="{{ company_route('quotes.index') }}"
                      class="nav-link {{ $isActive('quotes.*') ? 'active' : '' }}">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Quotes</p>
                    </a>
                  </li>
                @endif

                @if (Route::has('invoices.index'))
                  <li class="nav-item">
                    <a href="{{ company_route('invoices.index') }}"
                      class="nav-link {{ $isActive('invoices.*') ? 'active' : '' }}">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Invoices</p>
                    </a>
                  </li>
                @endif

                @if (Route::has('credit-notes.index'))
                  <li class="nav-item">
                    <a href="{{ company_route('credit-notes.index') }}"
                      class="nav-link {{ $isActive('credit-notes.*') ? 'active' : '' }}">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Credit Notes</p>
                    </a>
                  </li>
                @endif

              </ul>
            </li>
          @endif

          {{-- POS --}}
          @if ($hasPos)
            <li class="nav-item has-treeview {{ $isActive(['pos.*']) ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isActive(['pos.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-cash-register"></i>
                <p>POS <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">

                @if (Route::has('pos.orders.index'))
                  <li class="nav-item">
                    <a href="{{ company_route('pos.orders.index') }}"
                      class="nav-link {{ $isActive('pos.orders.*') ? 'active' : '' }}">
                      <i class="far fa-circle nav-icon"></i>
                      <p>POS Orders</p>
                    </a>
                  </li>
                @endif

                @if (Route::has('pos.returns.index'))
                  <li class="nav-item">
                    <a href="{{ company_route('pos.returns.index') }}"
                      class="nav-link {{ $isActive('pos.returns.*') ? 'active' : '' }}">
                      <i class="far fa-circle nav-icon"></i>
                      <p>POS Returns</p>
                    </a>
                  </li>
                @endif

              </ul>
            </li>
          @endif

          {{-- Web --}}
          @if ($hasWeb)
            <li class="nav-item has-treeview {{ $isActive(['web.*']) ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isActive(['web.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-globe"></i>
                <p>Web <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                @if (Route::has('web.orders.index'))
                  <li class="nav-item">
                    <a href="{{ company_route('web.orders.index') }}"
                      class="nav-link {{ $isActive('web.orders.*') ? 'active' : '' }}">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Web Orders</p>
                    </a>
                  </li>
                @endif
              </ul>
            </li>
          @endif

          {{-- Accounting --}}
          @if ($hasAccounting)
            <li
              class="nav-item has-treeview {{ $isActive(['payments.*', 'allocations.*', 'refunds.*', 'payment-methods.*']) ? 'menu-open' : '' }}">
              <a href="#"
                class="nav-link {{ $isActive(['payments.*', 'allocations.*', 'refunds.*', 'payment-methods.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                <p>Accounting <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                @if (Route::has('payments.index'))
                  <li class="nav-item"><a href="{{ company_route('payments.index') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Payments</p>
                    </a></li>
                @endif
                @if (Route::has('allocations.index'))
                  <li class="nav-item"><a href="{{ company_route('allocations.index') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Allocations</p>
                    </a></li>
                @endif
                @if (Route::has('refunds.index'))
                  <li class="nav-item"><a href="{{ company_route('refunds.index') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Refunds</p>
                    </a></li>
                @endif
                @if (Route::has('payment-methods.index'))
                  <li class="nav-item"><a href="{{ company_route('payment-methods.index') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Payment Methods</p>
                    </a></li>
                @endif
              </ul>
            </li>
          @endif

          {{-- Inventory --}}
          @if ($hasInventory)
            <li
              class="nav-item has-treeview {{ $isActive(['company.catalog.categories.*', 'company.catalog.products.*', 'company.catalog.attributes.*']) ? 'menu-open' : '' }}">
              <a href="#"
                class="nav-link {{ $isActive(['company.catalog.categories.*', 'company.catalog.products.*', 'company.catalog.attributes.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-boxes"></i>
                <p>Catalog <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                @if (Route::has('company.catalog.categories.index'))
                  <li class="nav-item"><a href="{{ company_route('company.catalog.categories.index') }}"
                      class="nav-link {{ $isActive('company.catalog.categories.*') ? 'active' : '' }}"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Categories</p>
                    </a></li>
                @endif
                @if (Route::has('company.catalog.attributes.index'))
                  <li class="nav-item"><a href="{{ company_route('company.catalog.attributes.index') }}"
                      class="nav-link {{ $isActive('company.catalog.attributes.*') ? 'active' : '' }}"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Attributes</p>
                    </a></li>
                @endif
                @if (Route::has('company.catalog.products.index'))
                  <li class="nav-item"><a href="{{ company_route('company.catalog.products.index') }}"
                      class="nav-link {{ $isActive('company.catalog.products.*') ? 'active' : '' }}"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Products</p>
                    </a></li>
                @endif
              </ul>
            </li>
          @endif

          {{-- Inventory Management --}}
          @if ($hasInventory)
            <li class="nav-item has-treeview {{ $isActive(['company.inventory.*']) ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isActive(['company.inventory.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-warehouse"></i>
                <p>Inventory <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item"><a href="{{ company_route('company.inventory.inventory.index') }}"
                    class="nav-link {{ $isActive('company.inventory.inventory.index') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Dashboard</p>
                  </a></li>
                <li class="nav-item"><a href="{{ company_route('company.inventory.transfers.index') }}"
                    class="nav-link {{ $isActive('company.inventory.transfers.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Transfers</p>
                  </a></li>
                <li class="nav-item"><a href="{{ company_route('company.inventory.movements') }}"
                    class="nav-link {{ $isActive('company.inventory.movements') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Movement History</p>
                  </a></li>
                <li class="nav-item"><a href="{{ company_route('company.inventory.inventory.create') }}"
                    class="nav-link {{ $isActive('company.inventory.inventory.create') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Adjust Stock</p>
                  </a></li>
                <li class="nav-item"><a href="{{ company_route('company.inventory.warehouses.index') }}"
                    class="nav-link {{ $isActive('company.inventory.warehouses.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Warehouses</p>
                  </a></li>
                <li class="nav-item"><a href="{{ company_route('company.inventory.stores.index') }}"
                    class="nav-link {{ $isActive('company.inventory.stores.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Stores</p>
                  </a></li>
                <li class="nav-item"><a href="{{ company_route('company.inventory.reasons.index') }}"
                    class="nav-link {{ $isActive('company.inventory.reasons.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Inv. Reasons</p>
                  </a></li>
              </ul>
            </li>
          @endif

          {{-- Customers --}}
          @if ($hasCustomers)
            <li
              class="nav-item has-treeview {{ $isActive(['customers.*', 'customer-balances.*']) ? 'menu-open' : '' }}">
              <a href="#"
                class="nav-link {{ $isActive(['customers.*', 'customer-balances.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-friends"></i>
                <p>Customers <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                @if (Route::has('customers.index'))
                  <li class="nav-item"><a href="{{ company_route('customers.index') }}"
                      class="nav-link {{ $isActive('customers.*') ? 'active' : '' }}"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Customers</p>
                    </a></li>
                @endif
                @if (Route::has('customer-balances.index'))
                  <li class="nav-item"><a href="{{ company_route('customer-balances.index') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Customer Balances</p>
                    </a></li>
                @endif
              </ul>
            </li>
          @endif

          {{-- Reports --}}
          @if ($hasReports)
            <li class="nav-item has-treeview {{ $isActive(['reports.*']) ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $isActive(['reports.*']) ? 'active' : '' }}">
                <i class="nav-icon fas fa-chart-line"></i>
                <p>Reports <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                @if (Route::has('reports.sales'))
                  <li class="nav-item"><a href="{{ company_route('reports.sales') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Sales Report</p>
                    </a></li>
                @endif
                @if (Route::has('reports.payments'))
                  <li class="nav-item"><a href="{{ company_route('reports.payments') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Payment Report</p>
                    </a></li>
                @endif
                @if (Route::has('reports.allocations'))
                  <li class="nav-item"><a href="{{ company_route('reports.allocations') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Allocation Report</p>
                    </a></li>
                @endif
                @if (Route::has('reports.outstanding'))
                  <li class="nav-item"><a href="{{ company_route('reports.outstanding') }}" class="nav-link"><i
                        class="far fa-circle nav-icon"></i>
                      <p>Outstanding Balances</p>
                    </a></li>
                @endif
              </ul>
            </li>
          @endif

        @endif

        <li class="nav-header">ACCESS CONTROL</li>

        {{-- Users --}}
        <li class="nav-item">
          <a href="{{ safe_route('users.index') }}" class="nav-link {{ $isActive('users.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-users"></i>
            <p>Users</p>
          </a>
        </li>

        @if (company_selected())
          <li class="nav-item">
            <a href="{{ company_route('company.settings.roles.index') }}" class="nav-link">
              <i class="nav-icon fas fa-user-shield"></i>
              <p>Roles</p>
            </a>
          </li>
        @endif


        {{-- Permissions (safe) --}}
        <li class="nav-item">
          <a href="{{ safe_route('permissions.index') }}"
            class="nav-link {{ $isActive('permissions.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-key"></i>
            <p>Permissions</p>
          </a>
        </li>

        @if (company_selected())
          <li class="nav-item">
            <a href="{{ company_route('company.settings.general') }}"
              class="nav-link {{ $isActive('company.settings.general') ? 'active' : '' }}">
              <i class="nav-icon fas fa-cogs"></i>
              <p>Company Profile</p>
            </a>
          </li>
        @endif

        {{-- Modules (safe) --}}
        <li class="nav-item">
          <a href="{{ safe_route('modules.index') }}" class="nav-link {{ $isActive('modules.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-layer-group"></i>
            <p>Modules</p>
          </a>
        </li>

        <li class="nav-header">SETTINGS</li>

        {{-- Companies --}}
        <li class="nav-item">
          <a href="{{ safe_route('companies.index') }}"
            class="nav-link {{ $isActive('companies.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-building"></i>
            <p>Companies</p>
          </a>
        </li>

        {{-- Future items --}}
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-money-bill-wave"></i>
            <p>Currencies</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-file-invoice"></i>
            <p>Payment Terms</p>
          </a>
        </li>

      </ul>
    </nav>

  </div>
</aside>
