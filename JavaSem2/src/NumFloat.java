public class NumFloat implements Num {
Float d;
public NumFloat(Float d) {
this.d = d;
}
@Override
public void neg() {
this.d = -d;
}
@Override
public void sqrt() {
this.d = (float) Math.sqrt(d);
}

@Override
public String toString() {
return d.toString();
}
}