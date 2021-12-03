import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ProductionStatusUpdateComponent } from './production-status-update.component';

describe('ProductionStatusUpdateComponent', () => {
  let component: ProductionStatusUpdateComponent;
  let fixture: ComponentFixture<ProductionStatusUpdateComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ProductionStatusUpdateComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ProductionStatusUpdateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
